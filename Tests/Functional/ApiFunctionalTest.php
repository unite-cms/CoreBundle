<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 11.01.18
 * Time: 16:55
 */

namespace UnitedCMS\CoreBundle\Tests\Functional;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;
use UnitedCMS\CoreBundle\Controller\GraphQLApiController;
use UnitedCMS\CoreBundle\Entity\ApiClient;
use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\Entity\Organization;
use UnitedCMS\CoreBundle\Service\UnitedCMSManager;
use UnitedCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class ApiFunctionalTestCase extends DatabaseAwareTestCase
{

    protected $data = [
        'foo_organization' => [
            '{
  "title": "Marketing & PR",
  "identifier": "marketing",
  "roles": [
    "ROLE_PUBLIC",
    "ROLE_EDITOR"
  ],
  "content_types": [
    {
      "title": "News",
      "identifier": "news",
      "fields": [
        {
          "title": "Title",
          "identifier": "title",
          "type": "text",
          "settings": {}
        },
        {
          "title": "Content",
          "identifier": "content",
          "type": "textarea",
          "settings": {}
        },
        {
          "title": "Category",
          "identifier": "category",
          "type": "reference",
          "settings": {
            "domain": "marketing",
            "content_type": "news_category"
          }
        }
      ],
      "collections": [
        {
          "title": "All",
          "identifier": "all",
          "type": "table",
          "settings": {}
        }
      ],
      "permissions": {
        "view content": [
          "ROLE_PUBLIC",
          "ROLE_EDITOR"
        ],
        "list content": [
          "ROLE_PUBLIC",
          "ROLE_EDITOR"
        ],
        "create content": [
          "ROLE_EDITOR"
        ],
        "update content": [
          "ROLE_EDITOR"
        ],
        "delete content": [
          "ROLE_EDITOR"
        ]
      },
      "locales": []
    },
    {
      "title": "News Category",
      "identifier": "news_category",
      "fields": [
        {
          "title": "Name",
          "identifier": "name",
          "type": "text",
          "settings": {}
        }
      ],
      "collections": [
        {
          "title": "All",
          "identifier": "all",
          "type": "table",
          "settings": {}
        }
      ],
      "permissions": {
        "view content": [
          "ROLE_PUBLIC",
          "ROLE_EDITOR"
        ],
        "list content": [
          "ROLE_PUBLIC",
          "ROLE_EDITOR"
        ],
        "create content": [
          "ROLE_EDITOR"
        ],
        "update content": [
          "ROLE_EDITOR"
        ],
        "delete content": [
          "ROLE_EDITOR"
        ]
      },
      "locales": []
    }
  ],
  "setting_types": [
    {
      "title": "Website",
      "identifier": "website",
      "fields": [
        {
          "title": "Title",
          "identifier": "title",
          "type": "text",
          "settings": {}
        },
        {
          "title": "Imprint",
          "identifier": "imprint",
          "type": "textarea",
          "settings": {}
        }
      ],
      "permissions": {
        "view setting": [
          "ROLE_EDITOR"
        ],
        "update setting": [
          "ROLE_EDITOR"
        ]
      },
      "locales": []
    }
  ]
}',
'{
  "title": "Internal Content",
  "identifier": "intern",
  "roles": [
    "ROLE_EDITOR"
  ],
  "content_types": [
    {
      "title": "Time Tracking",
      "identifier": "time_tracking",
      "fields": [
        {
          "title": "Employee",
          "identifier": "employee",
          "type": "text",
          "settings": {}
        },
        {
          "title": "Package",
          "identifier": "package",
          "type": "reference",
          "settings": {
            "domain": "intern",
            "content_type": "package"
          }
        }
      ],
      "collections": [
        {
          "title": "All",
          "identifier": "all",
          "type": "table",
          "settings": {}
        }
      ],
      "permissions": {
        "view content": [
          "ROLE_EDITOR"
        ],
        "list content": [
          "ROLE_EDITOR"
        ],
        "create content": [
          "ROLE_EDITOR"
        ],
        "update content": [
          "ROLE_EDITOR"
        ],
        "delete content": [
          "ROLE_EDITOR"
        ]
      },
      "locales": []
    },
    {
      "title": "Working Packages",
      "identifier": "package",
      "fields": [
        {
          "title": "Title",
          "identifier": "title",
          "type": "text",
          "settings": {}
        }
      ],
      "collections": [
        {
          "title": "All",
          "identifier": "all",
          "type": "table",
          "settings": {}
        }
      ],
      "permissions": {
        "view content": [
          "ROLE_EDITOR"
        ],
        "list content": [
          "ROLE_EDITOR"
        ],
        "create content": [
          "ROLE_EDITOR"
        ],
        "update content": [
          "ROLE_EDITOR"
        ],
        "delete content": [
          "ROLE_EDITOR"
        ]
      },
      "locales": []
    }
  ],
  "setting_types": []
}'
        ],
        'baa_organization' => [
            '{
  "title": "Marketing & PR",
  "identifier": "marketing",
  "roles": [
    "ROLE_PUBLIC",
    "ROLE_EDITOR"
  ],
  "content_types": [
    {
      "title": "News",
      "identifier": "news",
      "fields": [
        {
          "title": "Title",
          "identifier": "title",
          "type": "text",
          "settings": {}
        },
        {
          "title": "Content",
          "identifier": "content",
          "type": "textarea",
          "settings": {}
        },
        {
          "title": "Category",
          "identifier": "category",
          "type": "reference",
          "settings": {
            "domain": "marketing",
            "content_type": "news_category"
          }
        }
      ],
      "collections": [
        {
          "title": "All",
          "identifier": "all",
          "type": "table",
          "settings": {}
        }
      ],
      "permissions": {
        "view content": [
          "ROLE_PUBLIC",
          "ROLE_EDITOR"
        ],
        "list content": [
          "ROLE_PUBLIC",
          "ROLE_EDITOR"
        ],
        "create content": [
          "ROLE_EDITOR"
        ],
        "update content": [
          "ROLE_EDITOR"
        ],
        "delete content": [
          "ROLE_EDITOR"
        ]
      },
      "locales": []
    },
    {
      "title": "News Category",
      "identifier": "news_category",
      "fields": [
        {
          "title": "Name",
          "identifier": "name",
          "type": "text",
          "settings": {}
        }
      ],
      "collections": [
        {
          "title": "All",
          "identifier": "all",
          "type": "table",
          "settings": {}
        }
      ],
      "permissions": {
        "view content": [
          "ROLE_PUBLIC",
          "ROLE_EDITOR"
        ],
        "list content": [
          "ROLE_PUBLIC",
          "ROLE_EDITOR"
        ],
        "create content": [
          "ROLE_EDITOR"
        ],
        "update content": [
          "ROLE_EDITOR"
        ],
        "delete content": [
          "ROLE_EDITOR"
        ]
      },
      "locales": []
    }
  ],
  "setting_types": [
    {
      "title": "Website",
      "identifier": "website",
      "fields": [
        {
          "title": "Title",
          "identifier": "title",
          "type": "text",
          "settings": {}
        },
        {
          "title": "Imprint",
          "identifier": "imprint",
          "type": "textarea",
          "settings": {}
        }
      ],
      "permissions": {
        "view setting": [
          "ROLE_EDITOR"
        ],
        "update setting": [
          "ROLE_EDITOR"
        ]
      },
      "locales": []
    }
  ]
}',
            '{
  "title": "Internal Content",
  "identifier": "intern",
  "roles": [
    "ROLE_EDITOR"
  ],
  "content_types": [
    {
      "title": "Time Tracking",
      "identifier": "time_tracking",
      "fields": [
        {
          "title": "Employee",
          "identifier": "employee",
          "type": "text",
          "settings": {}
        },
        {
          "title": "Package",
          "identifier": "package",
          "type": "reference",
          "settings": {
            "domain": "intern",
            "content_type": "package"
          }
        }
      ],
      "collections": [
        {
          "title": "All",
          "identifier": "all",
          "type": "table",
          "settings": {}
        }
      ],
      "permissions": {
        "view content": [
          "ROLE_EDITOR"
        ],
        "list content": [
          "ROLE_EDITOR"
        ],
        "create content": [
          "ROLE_EDITOR"
        ],
        "update content": [
          "ROLE_EDITOR"
        ],
        "delete content": [
          "ROLE_EDITOR"
        ]
      },
      "locales": []
    },
    {
      "title": "Working Packages",
      "identifier": "package",
      "fields": [
        {
          "title": "Title",
          "identifier": "title",
          "type": "text",
          "settings": {}
        }
      ],
      "collections": [
        {
          "title": "All",
          "identifier": "all",
          "type": "table",
          "settings": {}
        }
      ],
      "permissions": {
        "view content": [
          "ROLE_EDITOR"
        ],
        "list content": [
          "ROLE_EDITOR"
        ],
        "create content": [
          "ROLE_EDITOR"
        ],
        "update content": [
          "ROLE_EDITOR"
        ],
        "delete content": [
          "ROLE_EDITOR"
        ]
      },
      "locales": []
    }
  ],
  "setting_types": []
}'
        ],
    ];
    protected $roles = ['ROLE_PUBLIC', 'ROLE_EDITOR'];
    protected $domains = [];
    protected $users = [];

    /**
     * @var GraphQLApiController $controller
     */
    private $controller;

    public function setUp()
    {
        parent::setUp();

        // Create a full united CMS structure with different organizations, domains and users.
        foreach($this->data as $id => $domains) {
            $org = new Organization();
            $org->setIdentifier($id)->setTitle(ucfirst($id));
            $this->em->persist($org);
            $this->em->flush($org);

            foreach($domains as $domain_data) {
                $domain = $this->container->get('united.cms.domain_definition_parser')->parse($domain_data);
                $domain->setOrganization($org);
                $this->domains[$domain->getIdentifier()] = $domain;
                $this->em->persist($domain);
                $this->em->flush($domain);

                foreach($this->roles as $role) {
                    $this->users[$role] = new ApiClient();
                    $this->users[$role]->setName(ucfirst($role))->setRoles([$role]);
                    $this->users[$role]->setDomain($domain);

                    $this->em->persist($this->users[$role]);
                    $this->em->flush($this->users[$role]);
                }
            }
        }

        $this->controller = new GraphQLApiController();
        $this->controller->setContainer($this->container);
    }

    private function api(Domain $domain, UserInterface $user, string $query, array $variables = []) {
        $reflector = new \ReflectionProperty(UnitedCMSManager::class, 'domain');
        $reflector->setAccessible(true);
        $reflector->setValue($this->container->get('united.cms.manager'), $domain);
        $reflector = new \ReflectionProperty(UnitedCMSManager::class, 'organization');
        $reflector->setAccessible(true);
        $reflector->setValue($this->container->get('united.cms.manager'), $domain->getOrganization());
        $reflector = new \ReflectionProperty(UnitedCMSManager::class, 'initialized');
        $reflector->setAccessible(true);
        $reflector->setValue($this->container->get('united.cms.manager'), true);

        $this->container->get('security.token_storage')->setToken(new UsernamePasswordToken($user, null, 'united_core_api_client', $user->getRoles()));

        $request = new Request([], [], [
            'organization' => $domain->getOrganization(),
            'domain' => $domain,
        ], [], [], [
            'REQUEST_METHOD' => 'POST',
        ], json_encode(['query' => $query, 'variables' => $variables]));
        $response = $this->controller->indexAction($domain->getOrganization(), $domain, $request);
        return json_decode($response->getContent());
    }

    private function assertApiResponse($expected, $actual) {

        if(!is_string($expected)) {
            $expected = json_encode($expected);
        }

        $this->assertEquals(json_decode($expected), $actual);
    }

    public function testAccessingAPI() {
        $this->assertApiResponse([
            'data' => [
                'findNews' => [
                    'total' => 0
                ]
            ]
        ], $this->api(
            $this->domains['marketing'],
            $this->users['ROLE_PUBLIC'],'query {
                findNews {
                    total
                }
            }')
        );
    }

    // TODO: More advanced API tests for child fields, filter, sort etc.

}