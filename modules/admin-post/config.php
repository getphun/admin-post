<?php
/**
 * admin-post config file
 * @package admin-post
 * @version 0.0.1
 * @upgrade true
 */

return [
    '__name' => 'admin-post',
    '__version' => '0.0.1',
    '__git' => 'https://github.com/getphun/admin-post',
    '__files' => [
        'modules/admin-post'                    => [ 'install', 'remove', 'update' ],
        'theme/admin/post/index.phtml'          => [ 'install', 'remove', 'update' ],
        'theme/admin/post/edit.phtml'           => [ 'install', 'remove', 'update' ],
        'theme/admin/post/statistic.phtml'      => [ 'install', 'remove', 'update' ],
        'theme/admin/static/js/admin-post.js'   => [ 'install', 'remove', 'update' ],
        'theme/admin/form/post_checkbox-tree.phtml' => [ 'install', 'remove', 'update' ],
        'etc/post/trash'                        => [ 'install', 'remove' ]
    ],
    '__dependencies' => [
        'admin',
        'post',
        '/post-category',
        '/post-tag',
        'site-param'
    ],
    '_services' => [],
    '_autoload' => [
        'classes' => [
            'AdminPost\\Controller\\PostController' => 'modules/admin-post/controller/PostController.php',
            'AdminPost\\Model\\PostHistory' => 'modules/admin-post/model/PostHistory.php'
        ],
        'files' => []
    ],
    '_routes' => [
        'admin' => [
            'adminPost' => [
                'priority' => 0,
                'rule' => '/post',
                'handler' => 'AdminPost\\Controller\\Post::index'
            ],
            'adminPostEdit' => [
                'priority' => 0,
                'rule' => '/post/:id',
                'handler' => 'AdminPost\\Controller\\Post::edit'
            ],
            'adminPostPublish' => [
                'priority' => 0,
                'rule' => '/post/:id/publish',
                'handler' => 'AdminPost\\Controller\\Post::publish'
            ],
            'adminPostRemove' => [
                'priority' => 0,
                'rule' => '/post/:id/remove',
                'handler' => 'AdminPost\\Controller\\Post::remove'
            ],
            'adminPostStatistic' => [
                'priority' => 0,
                'rule' => '/post/:id/statistic',
                'handler' => 'AdminPost\\Controller\\Post::statistic'
            ]
        ]
    ],
    'admin' => [
        'menu' => [
            'post' => [
                'label' => 'Post',
                'icon' => 'newspaper-o',
                'order' => 10,
                'submenu' => [
                    'post' => [
                        'label' => 'All Post',
                        'perms' => 'read_post',
                        'target' => 'adminPost',
                        'order' => 30
                    ]
                ]
            ]
        ]
    ],
    'formatter' => [
        'post_history' => [
            'user' => [
                'type' => 'object',
                'model' => 'User\\Model\\User'
            ],
            'type' => [
                'type' => 'enum',
                'form' => [
                    'name' => 'admin-post-history',
                    'field' => 'type'
                ]
            ],
            'created' => 'date'
        ]
    ],
    'form' => [
        'admin-post' => [
            'title' => [
                'type'      => 'text',
                'label'     => 'Title',
                'rules'     => [
                    'required'  => true
                ]
            ],
            'slug' => [
                'type'      => 'text',
                'label'     => 'Slug',
                'attrs'     => [
                    'data-slug' => '#field-title'
                ],
                'rules'     => [
                    'required'  => true,
                    'alnumdash' => true,
                    'unique' => [
                        'model' => 'Post\\Model\\Post',
                        'field' => 'slug',
                        'self'  => [
                            'uri'   => 'id',
                            'field' => 'id'
                        ]
                    ]
                ]
            ],
            'content' => [
                'type'      => 'wysiwyg',
                'label'     => 'Content',
                'rules'     => []
            ],
            'status' => [
                'type'      => 'select',
                'label'     => 'Status',
                'rules'     => [
                    'required' => true
                ]
            ],
            'featured' => [
                'type'      => 'checkbox',
                'label'     => 'Featured',
                'rules'     => []
            ],
            'editor_pick' => [
                'type'      => 'checkbox',
                'label'     => 'Editor Pick',
                'rules'     => []
            ],
            'cover' => [
                'type'      => 'file',
                'label'     => 'Cover',
                'rules'     => [
                    'file'      => 'image/*'
                ]
            ],
            'cover_label' => [
                'type'      => 'text',
                'label'     => 'Cover Label',
                'rules'     => []
            ],
            'embed' => [
                'type'      => 'textarea',
                'label'     => 'Embed',
                'rules'     => []
            ],
            'source' => [
                'type'      => 'text',
                'label'     => 'Source',
                'rules'     => []
            ],
            'published' => [
                'type'      => 'datetime',
                'label'     => 'Publish On',
                'rules'     => [
                    'date'      => 'Y-m-d H:i:s'
                ]
            ],
            'schema_type' => [
                'type'      => 'select',
                'label'     => 'Schema Type',
                'options'   => [
                    'Article'       => 'Article',
                    'BlogPosting'   => 'BlogPosting',
                    'CreativeWork'  => 'CreativeWork',
                    'NewsArticle'   => 'NewsArticle',
                    'Report'        => 'Report',
                    'Review'        => 'Review',
                    'TechArticle'   => 'TechArticle'
                ],
                'rules'     => []
            ],
            'meta_title' => [
                'type'      => 'text',
                'label'     => 'Meta Title',
                'rules'     => []
            ],
            'meta_description' => [
                'type'      => 'textarea',
                'label'     => 'Meta Description',
                'rules'     => []
            ],
            'meta_keywords' => [
                'type'      => 'text',
                'label'     => 'Meta Keywords',
                'rules'     => []
            ],
            
            'canal' => [
                'type'      => 'select-ajax',
                'label'     => 'Canal',
                'source'    => 'adminPostCanalFilter',
                'rules'     => []
            ],
            'category' => [
                'type'      => 'post_checkbox-tree',
                'label'     => 'Categories',
                'rules'     => []
            ],
            'tag' => [
                'type'      => 'multiple-ajax',
                'label'     => 'Tags',
                'source'    => 'adminPostTagFilter',
                'rules'     => []
            ]
        ],
        
        'admin-post-history' => [
            'type' => [
                'type'  => 'select',
                'label' => 'Type',
                'options' => [
                    1 => 'Create',
                    2 => 'Update',
                    3 => 'Schedule',
                    4 => 'Publish',
                    5 => 'Remove',
                    6 => 'Restore'
                ],
                'rules' => []
            ]
        ],
        
        'admin-post-index' => [
            'q' => [
                'type' => 'search',
                'label'=> 'Find Post',
                'nolabel'=> true,
                'rules'=> []
            ]
        ]
    ]
];