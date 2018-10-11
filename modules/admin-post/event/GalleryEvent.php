<?php
/**
 * Admin post gallery events
 * @package admin-post
 * @version 0.0.1
 * @upgrade true
 */

namespace AdminPost\Event;

use Post\Model\Post;

class GalleryEvent
{
    static function updated($object, $old=null){
        $id = $object->id;
        $posts = Post::get(['gallery'=>$id]);
        if(!$posts)
            return;
        Post::set(['gallery'=>null], ['gallery'=>$id]);

        foreach($posts as $post){
            $page = $dis->router->to('sitePostSingle', ['slug'=>$post->slug]);
            $dis->cache->removeOutput($page);
        }
    }
}