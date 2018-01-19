<?php

namespace UnitedCMS\CoreBundle\Controller;

use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use UnitedCMS\CoreBundle\Collection\CollectionTypeInterface;
use UnitedCMS\CoreBundle\Entity\Collection;
use UnitedCMS\CoreBundle\Entity\Content;
use UnitedCMS\CoreBundle\Entity\ContentInCollection;
use UnitedCMS\CoreBundle\Form\WebComponentType;
use UnitedCMS\CoreBundle\Security\ContentVoter;

class ContentController extends Controller
{
    /**
     * @Route("/{content_type}/{collection}")
     * @Method({"GET"})
     * @Entity("collection", expr="repository.findByIdentifiers(organization, domain, content_type, collection)")
     * @Security("is_granted(constant('UnitedCMS\\CoreBundle\\Security\\ContentVoter::LIST'), collection)")
     *
     * @param Collection $collection
     * @return Response
     */
    public function indexAction(Collection $collection)
    {
        return $this->render(
            'UnitedCMSCoreBundle:Content:index.html.twig',
            [
                'organization' => $collection->getContentType()->getDomain()->getOrganization(),
                'domain' => $collection->getContentType()->getDomain(),
                'collection' => $collection,
                'template' => $this->get('united.cms.collection_type_manager')->getCollectionType(
                    $collection->getType()
                )::getTemplate(),
                'templateParameters' => $this->get('united.cms.collection_type_manager')->getTemplateRenderParameters(
                    $collection
                ),
            ]
        );
    }

    /**
     * @Route("/{content_type}/{collection}/create")
     * @Method({"GET", "POST"})
     * @Entity("collection", expr="repository.findByIdentifiers(organization, domain, content_type, collection)")
     * @Security("is_granted(constant('UnitedCMS\\CoreBundle\\Security\\ContentVoter::CREATE'), collection)")
     *
     * @param Collection $collection
     * @param Request $request
     * @return Response
     */
    public function createAction(Collection $collection, Request $request)
    {
        $content = new Content();

        // Allow to set locale and translation of via GET parameters.
        if($request->query->has('locale')) {
            $content->setLocale($request->query->get('locale'));
        }

        if($request->query->has('translation_of')) {
            $translationOf = $this->getDoctrine()->getRepository('UnitedCMSCoreBundle:Content')->find($request->query->get('translation_of'));
            if($translationOf) {
                $content->setTranslationOf($translationOf);
            }
        }

        $form = $this->get('united.cms.fieldable_form_builder')->createForm($collection->getContentType(), $content);
        $form->add('submit', SubmitType::class, ['label' => 'Create']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            if(isset($data['locale'])) {
                $content->setLocale($data['locale']);
                unset($data['locale']);
            }

            $content
                ->setContentType($collection->getContentType())
                ->setData($data);

            $contentInCollection = new ContentInCollection();
            $contentInCollection->setCollection($collection);
            $content->addCollection($contentInCollection);

            $errors = $this->get('validator')->validate($content);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $form->addError(
                        new FormError(
                            $error->getMessage(),
                            $error->getMessageTemplate(),
                            $error->getParameters(),
                            $error->getPlural(),
                            $error->getCause()
                        )
                    );
                }
            } else {
                $this->getDoctrine()->getManager()->persist($content);
                $this->getDoctrine()->getManager()->flush();

                $this->addFlash('success', 'Content created.');

                return $this->redirectToRoute(
                    'unitedcms_core_content_index',
                    [
                        'organization' => $collection->getContentType()->getDomain()->getOrganization()->getIdentifier(
                        ),
                        'domain' => $collection->getContentType()->getDomain()->getIdentifier(),
                        'content_type' => $collection->getContentType()->getIdentifier(),
                        'collection' => $collection->getIdentifier(),
                    ]
                );
            }
        }

        return $this->render(
            'UnitedCMSCoreBundle:Content:create.html.twig',
            [
                'organization' => $collection->getContentType()->getDomain()->getOrganization(),
                'domain' => $collection->getContentType()->getDomain(),
                'collection' => $collection,
                'contentType' => $collection->getContentType(),
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{content_type}/{collection}/update/{content}")
     * @Method({"GET", "POST"})
     * @Entity("collection", expr="repository.findByIdentifiers(organization, domain, content_type, collection)")
     * @Entity("content")
     * @Security("is_granted(constant('UnitedCMS\\CoreBundle\\Security\\ContentVoter::UPDATE'), content)")
     *
     * @param Collection $collection
     * @param Content $content
     * @param Request $request
     * @return Response
     */
    public function updateAction(Collection $collection, Content $content, Request $request)
    {
        $form = $this->get('united.cms.fieldable_form_builder')->createForm($collection->getContentType(), $content);
        $form->add('submit', SubmitType::class, ['label' => 'Update']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();

            if(isset($data['locale'])) {
                $content->setLocale($data['locale']);
                unset($data['locale']);
            }

            $content->setData($data);

            $errors = $this->get('validator')->validate($content);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $form->addError(
                        new FormError(
                            $error->getMessage(),
                            $error->getMessageTemplate(),
                            $error->getParameters(),
                            $error->getPlural(),
                            $error->getCause()
                        )
                    );
                }
            } else {
                $this->getDoctrine()->getManager()->flush();

                $this->addFlash('success', 'Content updated.');

                return $this->redirectToRoute(
                    'unitedcms_core_content_index',
                    [
                        'organization' => $collection->getContentType()->getDomain()->getOrganization()->getIdentifier(
                        ),
                        'domain' => $collection->getContentType()->getDomain()->getIdentifier(),
                        'content_type' => $collection->getContentType()->getIdentifier(),
                        'collection' => $collection->getIdentifier(),
                    ]
                );
            }
        }

        return $this->render(
            'UnitedCMSCoreBundle:Content:update.html.twig',
            [
                'organization' => $collection->getContentType()->getDomain()->getOrganization(),
                'domain' => $collection->getContentType()->getDomain(),
                'collection' => $collection,
                'contentType' => $collection->getContentType(),
                'content' => $content,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{content_type}/{collection}/delete/{content}")
     * @Method({"GET", "POST"})
     * @Entity("collection", expr="repository.findByIdentifiers(organization, domain, content_type, collection)")
     * @Entity("content")
     * @Security("is_granted(constant('UnitedCMS\\CoreBundle\\Security\\ContentVoter::DELETE'), content)")
     *
     * @param Collection $collection
     * @param Content $content
     * @param Request $request
     * @return Response
     */
    public function deleteAction(Collection $collection, Content $content, Request $request)
    {

        $form = $this->createFormBuilder()
            ->add('submit', SubmitType::class, ['label' => 'Delete'])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $errors = $this->get('validator')->validate($content, null, ['DELETE']);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $form->addError($error);
                }
            } else {
                $this->getDoctrine()->getManager()->remove($content);
                $this->getDoctrine()->getManager()->flush();

                $this->addFlash('success', 'Content deleted.');

                return $this->redirectToRoute(
                    'unitedcms_core_content_index',
                    [
                        'organization' => $collection->getContentType()->getDomain()->getOrganization()->getIdentifier(
                        ),
                        'domain' => $collection->getContentType()->getDomain()->getIdentifier(),
                        'content_type' => $collection->getContentType()->getIdentifier(),
                        'collection' => $collection->getIdentifier(),
                    ]
                );
            }
        }

        return $this->render(
            'UnitedCMSCoreBundle:Content:delete.html.twig',
            [
                'organization' => $collection->getContentType()->getDomain()->getOrganization(),
                'domain' => $collection->getContentType()->getDomain(),
                'collection' => $collection,
                'contentType' => $collection->getContentType(),
                'content' => $content,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{content_type}/{collection}/delete-definitely/{content}")
     * @Method({"GET", "POST"})
     * @Entity("collection", expr="repository.findByIdentifiers(organization, domain, content_type, collection)")
     * @param Collection $collection
     * @param string $content
     * @param Request $request
     * @return Response
     */
    public function deleteDefinitelyAction(Collection $collection, string $content, Request $request)
    {

        $em = $this->getDoctrine()->getManager();

        if($em instanceof EntityManager) {
            $em->getFilters()->disable('gedmo_softdeleteable');
        }

        $content = $em->getRepository('UnitedCMSCoreBundle:Content')->findOneBy([
            'id' => $content,
            'contentType' => $collection->getContentType(),
        ]);

        if($em instanceof EntityManager) {
            $em->getFilters()->enable('gedmo_softdeleteable');
        }

        if(!$content) {
            throw $this->createNotFoundException();
        }

        if(!$this->isGranted(ContentVoter::UPDATE, $content)) {
            throw $this->createAccessDeniedException();
        }

        if($content->getDeleted() == null) {
            throw $this->createNotFoundException();
        }

        $form = $this->createFormBuilder()
            ->add('submit', SubmitType::class, ['label' => 'Delete definitely'])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $errors = $this->get('validator')->validate($content, null, ['DELETE']);

            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $form->addError($error);
                }
            } else {

                // Get log entries and delete them.
                foreach($em->getRepository('GedmoLoggable:LogEntry')->getLogEntries($content) as $logEntry) {
                    $em->remove($logEntry);
                }

                // Delete content item.
                $em->remove($content);
                $em->flush();

                $this->addFlash('success', 'Content deleted.');

                return $this->redirectToRoute(
                    'unitedcms_core_content_index',
                    [
                        'organization' => $collection->getContentType()->getDomain()->getOrganization()->getIdentifier(
                        ),
                        'domain' => $collection->getContentType()->getDomain()->getIdentifier(),
                        'content_type' => $collection->getContentType()->getIdentifier(),
                        'collection' => $collection->getIdentifier(),
                    ]
                );
            }
        }

        return $this->render(
            'UnitedCMSCoreBundle:Content:deleteDefinitely.html.twig',
            [
                'organization' => $collection->getContentType()->getDomain()->getOrganization(),
                'domain' => $collection->getContentType()->getDomain(),
                'collection' => $collection,
                'contentType' => $collection->getContentType(),
                'content' => $content,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{content_type}/{collection}/recover/{content}")
     * @Method({"GET", "POST"})
     * @Entity("collection", expr="repository.findByIdentifiers(organization, domain, content_type, collection)")
     * @param Collection $collection
     * @param string $content
     * @param Request $request
     * @return Response
     */
    public function recoverAction(Collection $collection, string $content, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        if($em instanceof EntityManager) {
            $em->getFilters()->disable('gedmo_softdeleteable');
        }

        $content = $em->getRepository('UnitedCMSCoreBundle:Content')->findOneBy([
            'id' => $content,
            'contentType' => $collection->getContentType(),
        ]);

        if($em instanceof EntityManager) {
            $em->getFilters()->enable('gedmo_softdeleteable');
        }

        if(!$content) {
            throw $this->createNotFoundException();
        }

        if(!$this->isGranted(ContentVoter::UPDATE, $content)) {
            throw $this->createAccessDeniedException();
        }

        if($content->getDeleted() == null) {
            throw $this->createNotFoundException();
        }

        $form = $this->createFormBuilder()
            ->add('submit', SubmitType::class, ['label' => 'Restore deleted content'])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $errors = $this->get('validator')->validate($content, null, ['DELETE']);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $form->addError($error);
                }
            } else {
                $content->recoverDeleted();
                $this->getDoctrine()->getManager()->flush();
                $this->addFlash('success', 'Deleted content was restored.');

                return $this->redirectToRoute(
                    'unitedcms_core_content_index',
                    [
                        'organization' => $collection->getContentType()->getDomain()->getOrganization()->getIdentifier(
                        ),
                        'domain' => $collection->getContentType()->getDomain()->getIdentifier(),
                        'content_type' => $collection->getContentType()->getIdentifier(),
                        'collection' => $collection->getIdentifier(),
                    ]
                );
            }
        }

        return $this->render(
            '@UnitedCMSCore/Content/recover.html.twig',
            [
                'organization' => $collection->getContentType()->getDomain()->getOrganization(),
                'domain' => $collection->getContentType()->getDomain(),
                'collection' => $collection,
                'contentType' => $collection->getContentType(),
                'content' => $content,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{content_type}/{collection}/translations/{content}")
     * @Method({"GET", "POST"})
     * @Entity("collection", expr="repository.findByIdentifiers(organization, domain, content_type, collection)")
     * @Entity("content")
     * @Security("is_granted(constant('UnitedCMS\\CoreBundle\\Security\\ContentVoter::UPDATE'), content)")
     *
     * @param Collection $collection
     * @param Content $content
     * @param Request $request
     * @return Response
     */
    public function translationsAction(Collection $collection, Content $content, Request $request)
    {

        if(!empty($content->getTranslationOf())) {
            // Check if the translationOf content was soft deleted.
            if(!$this->getDoctrine()->getRepository('UnitedCMSCoreBundle:Content')->findOneBy(['id' => $content->getTranslationOf()->getId()])) {
                $this->addFlash('warning', 'You cannot manage translations for this content, because it is a translation of soft-deleted content.');
                return $this->redirectToRoute(
                    'unitedcms_core_content_index',
                    [
                        'organization' => $collection->getContentType()->getDomain()->getOrganization()->getIdentifier(
                        ),
                        'domain' => $collection->getContentType()->getDomain()->getIdentifier(),
                        'content_type' => $collection->getContentType()->getIdentifier(),
                        'collection' => $collection->getIdentifier(),
                    ]
                );
            }
        }

        return $this->render(
            '@UnitedCMSCore/Content/translations.html.twig',
            [
                'collection' => $collection,
                'contentType' => $collection->getContentType(),
                'content' => $content,
            ]
        );
    }

    /**
     * @Route("/{content_type}/{collection}/translations/{content}/add/{locale}")
     * @Method({"GET", "POST"})
     * @Entity("collection", expr="repository.findByIdentifiers(organization, domain, content_type, collection)")
     * @Entity("content")
     * @Security("is_granted(constant('UnitedCMS\\CoreBundle\\Security\\ContentVoter::UPDATE'), content)")
     *
     * @param Collection $collection
     * @param Content $content
     * @param String $locale
     * @param Request $request
     * @return Response
     */
    public function addTranslationAction(Collection $collection, Content $content, String $locale, Request $request)
    {

        $form = $this->createFormBuilder()
            ->add('translation', WebComponentType::class, [
                    'tag' => 'united-cms-core-reference-field',
                    'empty_data' => [
                        'domain' => $collection->getContentType()->getDomain()->getIdentifier(),
                        'content_type' => $collection->getContentType()->getIdentifier(),
                    ],
                    'attr' => [
                        'base-url' => '/' . $collection->getContentType()->getDomain()->getOrganization() . '/',
                        'content-label' => '#{id}',
                        'modal-html' => $this->render(
                            $this->get('united.cms.collection_type_manager')->getCollectionType($collection->getType())::getTemplate(),
                            [
                                'collection' => $collection,
                                'parameters' => $this->get('united.cms.collection_type_manager')->getTemplateRenderParameters($collection, CollectionTypeInterface::SELECT_MODE_SINGLE),
                            ]
                        ),
                    ],
                ])
            ->add('submit', SubmitType::class, ['label' => 'Save as Translation'])->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            foreach($form->getData() as $key => $translation_identifier) {
                if(!empty($translation_identifier['content'])) {
                    $translation = $this->getDoctrine()->getRepository('UnitedCMSCoreBundle:Content')->findOneBy([
                        'id' => $translation_identifier['content'],
                        'translationOf' => NULL,
                    ]);
                    if(!$translation) {

                        $form->addError(
                            new FormError(
                                'validation.content_not_found',
                                'validation.content_not_found'
                            )
                        );

                    } else {
                        $content->addTranslation($translation);

                        $errors = $this->get('validator')->validate($content);
                        if (count($errors) > 0) {
                            foreach ($errors as $error) {
                                $form->addError(
                                    new FormError(
                                        $error->getMessage(),
                                        $error->getMessageTemplate(),
                                        $error->getParameters(),
                                        $error->getPlural(),
                                        $error->getCause()
                                    )
                                );
                            }

                        } else {
                            $this->getDoctrine()->getManager()->flush();
                            $this->addFlash('success', 'Translation added.');
                            return $this->redirectToRoute(
                                'unitedcms_core_content_translations',
                                [
                                    'organization' => $collection->getContentType()->getDomain()->getOrganization(
                                    )->getIdentifier(),
                                    'domain' => $collection->getContentType()->getDomain()->getIdentifier(),
                                    'content_type' => $collection->getContentType()->getIdentifier(),
                                    'collection' => $collection->getIdentifier(),
                                    'content' => $content->getId(),
                                ]
                            );
                        }
                    }
                }
            }
        }

        return $this->render(
            'UnitedCMSCoreBundle:Content:addTranslation.html.twig',
            [
                'organization' => $collection->getContentType()->getDomain()->getOrganization(),
                'domain' => $collection->getContentType()->getDomain(),
                'collection' => $collection,
                'contentType' => $collection->getContentType(),
                'content' => $content,
                'locale' => $locale,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{content_type}/{collection}/translations/{content}/remove/{locale}")
     * @Method({"GET", "POST"})
     * @Entity("collection", expr="repository.findByIdentifiers(organization, domain, content_type, collection)")
     * @Entity("content")
     * @Security("is_granted(constant('UnitedCMS\\CoreBundle\\Security\\ContentVoter::UPDATE'), content)")
     *
     * @param Collection $collection
     * @param Content $content
     * @param String $locale
     * @param Request $request
     * @return Response
     */
    public function removeTranslationAction(Collection $collection, Content $content, String $locale, Request $request)
    {
        $translations = $content->getTranslations()->filter(function(Content $content) use ($locale) { return $content->getLocale() == $locale; });

        if(empty($translations)) {
            throw $this->createNotFoundException();
        }

        /**
         * @var Content $translation
         */
        $translation = $translations->first();

        $form = $this->createFormBuilder()
            ->add('submit', SubmitType::class, ['label' => 'Remove'])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $translation->setTranslationOf(null);
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Translation removed.');

            return $this->redirectToRoute(
                'unitedcms_core_content_translations',
                [
                    'organization' => $collection->getContentType()->getDomain()->getOrganization()->getIdentifier(
                    ),
                    'domain' => $collection->getContentType()->getDomain()->getIdentifier(),
                    'content_type' => $collection->getContentType()->getIdentifier(),
                    'collection' => $collection->getIdentifier(),
                    'content' => $content->getId(),
                ]
            );
        }

        return $this->render(
            'UnitedCMSCoreBundle:Content:removeTranslation.html.twig',
            [
                'organization' => $collection->getContentType()->getDomain()->getOrganization(),
                'domain' => $collection->getContentType()->getDomain(),
                'collection' => $collection,
                'contentType' => $collection->getContentType(),
                'content' => $content,
                'translation' => $translation,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{content_type}/{collection}/revisions/{content}")
     * @Method({"GET"})
     * @Entity("collection", expr="repository.findByIdentifiers(organization, domain, content_type, collection)")
     * @Entity("content")
     * @Security("is_granted(constant('UnitedCMS\\CoreBundle\\Security\\ContentVoter::UPDATE'), content)")
     *
     * @param Collection $collection
     * @param Content $content
     * @param Request $request
     * @return Response
     */
    public function revisionsAction(Collection $collection, Content $content, Request $request)
    {
        return $this->render(
            '@UnitedCMSCore/Content/revisions.html.twig',
            [
                'collection' => $collection,
                'contentType' => $collection->getContentType(),
                'content' => $content,
                'revisions' => $this->getDoctrine()->getManager()->getRepository('GedmoLoggable:LogEntry')->getLogEntries($content),
            ]
        );
    }

    /**
     * @Route("/{content_type}/{collection}/revisions/{content}/revert/{version}")
     * @Method({"GET", "POST"})
     * @Entity("collection", expr="repository.findByIdentifiers(organization, domain, content_type, collection)")
     * @Entity("content")
     * @Security("is_granted(constant('UnitedCMS\\CoreBundle\\Security\\ContentVoter::UPDATE'), content)")
     *
     * @param Collection $collection
     * @param Content $content
     * @param int $version
     * @param Request $request
     * @return Response
     */
    public function revisionsRevertAction(Collection $collection, Content $content, int $version, Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('submit', SubmitType::class, ['label' => 'Revert'])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $this->getDoctrine()->getManager()->getRepository('GedmoLoggable:LogEntry')->revert($content, $version);
            $this->getDoctrine()->getManager()->persist($content);
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Content reverted.');

            return $this->redirectToRoute(
                'unitedcms_core_content_revisions',
                [
                    'organization' => $collection->getContentType()->getDomain()->getOrganization()->getIdentifier(),
                    'domain' => $collection->getContentType()->getDomain()->getIdentifier(),
                    'content_type' => $collection->getContentType()->getIdentifier(),
                    'collection' => $collection->getIdentifier(),
                    'content' => $content->getId(),
                ]
            );
        }

        return $this->render(
            '@UnitedCMSCore/Content/revertRevision.html.twig',
            [
                'organization' => $collection->getContentType()->getDomain()->getOrganization()->getIdentifier(),
                'domain' => $collection->getContentType()->getDomain()->getIdentifier(),
                'collection' => $collection,
                'contentType' => $collection->getContentType(),
                'content' => $content,
                'version' => $version,
                'form' => $form->createView(),
            ]
        );
    }
}