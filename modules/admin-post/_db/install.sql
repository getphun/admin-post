INSERT IGNORE INTO `user_perms` ( `name`, `group`, `role`, `about` ) VALUES
    ( 'create_post',  'Post', 'Reporter',   'Allow user to create new post' ),
    ( 'read_post',    'Post', 'Reporter',   'Allow user to view all owned posts' ),
    ( 'update_post',  'Post', 'Reporter',   'Allow user to update exists owned post' ),
    ( 'remove_post',  'Post', 'Editor',     'Allow user to remove exists owned post' ),
    
    ( 'read_post_statistic',    'Post', 'Editor', 'Allow user to view all post statistic' ),
    ( 'set_post_featured',      'Post', 'Editor', 'Allow user to set post as featured' ),
    ( 'set_post_editorpick',    'Post', 'Editor', 'Allow user to set post as editor pick' ),
    ( 'read_post_all',          'Post', 'Editor', 'Allow user to read all posts' ),
    ( 'update_post_all',        'Post', 'Editor', 'Allow user to update all posts' ),
    ( 'update_post_slug',       'Post', 'Editor', 'Allow user to update post slug' ),
    ( 'publish_post',           'Post', 'Editor', 'Allow user to publish the post' ),
    ( 'remove_post_all',        'Post', 'Editor', 'Allow user to remove exists post' );

CREATE TABLE IF NOT EXISTS `post_history` (
    `id` INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user` INTEGER NOT NULL,
    `post` INTEGER NOT NULL,
    -- 1 Create
    -- 2 Update
    -- 3 Schedule
    -- 4 Publish
    -- 5 Remove
    -- 6 Restore
    `type` TINYINT DEFAULT 2,
    `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);