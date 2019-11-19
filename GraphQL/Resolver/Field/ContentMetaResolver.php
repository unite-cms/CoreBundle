<?php


namespace UniteCMS\CoreBundle\GraphQL\Resolver\Field;

use InvalidArgumentException;
use Symfony\Component\Security\Core\Security;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Domain\DomainManager;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use UniteCMS\CoreBundle\Exception\ContentAccessDeniedException;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;

class ContentMetaResolver implements FieldResolverInterface
{
    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    /**
     * @var Security $security
     */
    protected $security;

    public function __construct(DomainManager $domainManager, Security $security)
    {
        $this->domainManager = $domainManager;
        $this->security = $security;
    }

    /**
     * @inheritDoc
     */
    public function supports(string $typeName, ObjectTypeDefinitionNode $typeDefinitionNode): bool {
        return $typeName === 'UniteContentMeta';
    }

    /**
     * @inheritDoc
     */
    public function resolve($value, $args, $context, ResolveInfo $info) {

        if(!$value instanceof ContentInterface) {
            throw new InvalidArgumentException(sprintf('ContentMetaResolver expects an instance of %s as value.', ContentInterface::class));
        }

        switch ($info->fieldName) {

            case 'id':
                return $value->getId();

            case 'deleted':
                return $value->getDeleted();

            case 'permissions':
                $permissions = [];
                foreach(ContentVoter::ENTITY_PERMISSIONS as  $permission) {
                    $permissions[$permission] = $this->security->isGranted($permission, $value);
                }
                return $permissions;

            case 'version':

                if(!$this->security->isGranted(ContentVoter::UPDATE, $value)) {
                    throw new ContentAccessDeniedException(sprintf('You need %s permission to see the content version.', ContentVoter::UPDATE));
                }

                $domain = $this->domainManager->current();
                $versions = $domain->getContentManager()->revisions($domain, $value, 1);
                return count($versions) > 0 ? $versions[0]->getVersion() : 0;

            case 'revisions':

                if(!$this->security->isGranted(ContentVoter::UPDATE, $value)) {
                    throw new ContentAccessDeniedException(sprintf('You need %s permission to view content revisions.', ContentVoter::UPDATE));
                }

                $domain = $this->domainManager->current();
                $limit = $args['limit'] ?? 20;
                $limit = $limit > 20 ? 20 : $limit;
                $offset = $args['offset'] ?? 0;
                return $domain->getContentManager()->revisions($domain, $value, $limit, $offset);
            default: return null;
        }
    }
}
