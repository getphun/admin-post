<?php
/**
 * Post controller
 * @package admin-post
 * @version 0.0.1
 * @upgrade true
 */

namespace AdminPost\Controller;

use AdminPost\Model\PostHistory as PHistory;

use Post\Model\Post as Post;
use Post\Model\PostContent as PContent;
use Post\Model\PostStatistic as PStatistic;

use PostCategory\Model\PostCategory as PCategory;
use PostCategory\Model\PostCategoryChain as PCChain;

use PostTag\Model\PostTag as PTag;
use PostTag\Model\PostTagChain as PTChain;

use PostFacebookInstantArticleFeed\Model\PostFbInstantArticle as PFBIArticle;

class PostController extends \AdminController
{
    private $oo_chains = [
        'post-category' => [
            'prop'  => 'category',
            'table' => 'post_category',
            'chain' => 'PostCategory\\Model\\PostCategoryChain',
            'model' => 'PostCategory\\Model\\PostCategory',
            'perms' => 'read_post_category'
        ],
        'post-tag' => [
            'prop'  => 'tag',
            'table' => 'post_tag',
            'chain' => 'PostTag\\Model\\PostTagChain',
            'model' => 'PostTag\\Model\\PostTag',
            'perms' => 'read_post_tag',
            'opts'  => 'tags'
        ]
    ];
    
    private $oo_props = [
        'post-canal' => [
            'prop'  => 'canal',
            'table' => 'post_canal',
            'model' => 'PostCanal\\Model\\PostCanal',
            'perms' => 'read_post_canal',
            'opts'  => 'canals'
        ]
    ];
    
    private $oo_models = [
        'post'                  => 'Post\\Model\\Post',
        'post_content'          => 'Post\\Model\\PostContent',
        'post_category_chain'   => 'PostCategory\\Model\\PostCategoryChain',
        'post_history'          => 'AdminPost\\Model\\PostHistory',
        'post_statistic'        => 'Post\\Model\\PostStatistic',
        'post_tag_chain'        => 'PostTag\\Model\\PostTagChain'
    ];
    
    private function _defaultParams(){
        return [
            'title'             => 'Post',
            'nav_title'         => 'Post',
            'active_menu'       => 'post',
            'active_submenu'    => 'post',
            'total'             => 0,
            'pagination'        => []
        ];
    }
    
    private function _optStatuses($object){
        $opts = [
            1 => 'Draft',
            2 => 'Editor'
        ];
        
        $worker_exists = module_exists('worker');
        
        if($this->can_i->publish_post){
            if($worker_exists)
                $opts[3] = 'Scheduled';
            $opts[4] = 'Published';
            
            return $opts;
        }
        
        if(!isset($object->status))
            return $opts;
        
        if(isset($opts[$object->status]))
            return $opts;
        
        if($object->status == 3)
            $opts[3] = 'Scheduled';
        elseif($object->status == 4)
            $opts[4] = 'Published';
            
        return $opts;
    }
    
    public function editAction(){
        if(!$this->user->login)
            return $this->show404();
        
        $id = $this->param->id;
        if(!$id && !$this->can_i->create_post)
            return $this->show404();
        
        $old = null;
        $params = $this->_defaultParams();
        $params['title'] = 'Create New Post';
        $params['ref'] = $this->req->getQuery('ref') ?? $this->router->to('adminPost');
        $params['jses'] = [ 'js/admin-post.js' ];
        $params['categories'] = [];
        
        $history = 1;
        
        $add_schedule = false;
        $remove_schedule = false;
        $object_content = null;
        if($id){
            $params['title'] = 'Edit Post';
            $object = Post::get($id, false);
            if(!$object)
                return $this->show404();
            
            if($object->user != $this->user->id){
                if(!$this->can_i->update_post_all)
                    return $this->show404();
            }elseif(!$this->can_i->update_post){
                if(!$this->can_i->update_post_all)
                    return $this->show404();
            }
            
            // get the old content
            $object_content = PContent::get(['post'=>$object->id], false);
            if($object_content)
                $object->content = $object_content->content;
            
            $old = clone $object;
            $history = 2;
        }else{
            $object = new \stdClass();
            $object->user = $this->user->id;
            $object->content = '';
            $object->status = 1;
            $old = clone $object;
        }
        
        $params['object'] = $object;
        
        // get statuses options
        $params['statuses'] = $statuses = $this->_optStatuses($object);
        
        // get all categories
        if(module_exists('post-category')){
            $categories = PCategory::get([], true);
            $params['categories'] = $categories;
        }
        
        // get all preset and/or posted chain data
        foreach($this->oo_chains as $module => $args){
            if(!module_exists($module))
                continue;
            if(!$this->can_i->{$args['perms']})
                continue;
            if(isset($args['opts']))
                $params[$args['opts']] = [];
            
            $old->{$args['prop']} = [];
            $object->{$args['prop']} = [];
            
            if($id){
                $oo_chain = $args['chain']::get(['post'=>$id], true);
                if($oo_chain){
                    $old_val = array_column($oo_chain, $args['table']);
                    $old->{$args['prop']} = $old_val;
                    $object->{$args['prop']} = $old_val;
                }
            }
            
            if($this->req->method == 'POST'){
                // get the data from user posted content
                $object->{$args['prop']} = $this->req->getPost($args['prop']);
            }
            
            // get preset options
            if(!isset($args['opts']) || !$object->{$args['prop']})
                continue;
                
            $oo_obj = $args['model']::get(['id'=>$object->{$args['prop']}], true);
            
            $params[$args['opts']] = array_column($oo_obj, 'name', 'id');
        }
        
        // get preset and/or posted prop object
        foreach($this->oo_props as $module => $args){
            if(!module_exists($module))
                continue;
            if(!$this->can_i->{$args['perms']})
                continue;
            $params[$args['opts']] = [];
            
            $oo_id = null;
            if($id)
                $oo_id = $object->{$args['prop']};
            
            if($this->req->method == 'POST')
                $oo_id = $this->req->getPost($args['prop']);
            
            if(!$oo_id)
                continue;
            
            $oo_obj = $args['model']::get(['id'=>$oo_id], false);
            if($oo_obj)
                $params[$args['opts']][$oo_obj->id] = $oo_obj->name;
        }
        
        // VALIDATE THE FORM
        if(false === ($form=$this->form->validate('admin-post', $object)))
            return $this->respond('post/edit', $params);
        
        $object = object_replace($object, $form);
        
        // let see the chains to remove/create
        $chains = [];
        foreach($this->oo_chains as $module => $args){
            $prop = $args['prop'];
            
            if(!property_exists($object, $prop))
                continue;
            $object_prop = $object->$prop ?? [];
            unset($object->$prop);
            
            if(!module_exists($module))
                continue;
            if(!$this->can_i->{$args['perms']})
                continue;
            
            $chains[$module] = [
                'insert' => array_diff($object_prop, $old->$prop),
                'remove' => array_diff($old->$prop, $object_prop)
            ];
            
            $oo_ids = array_unique(array_merge($object_prop, $old->$prop));
            
            // now call event update of each object
            $oo_obj = $args['model']::get(['id'=>$oo_ids], true);
            if(!$oo_obj)
                continue;
            foreach($oo_obj as $obj)
                $this->fire($module . ':updated', $obj, $obj);
        }
        
        // let see the effected ex-object
        foreach($this->oo_props as $module => $args){
            $prop = $args['prop'];
            
            if(!property_exists($object, $prop))
                continue;
            
            if(!module_exists($module))
                unset($object->$prop);
            if(!$this->can_i->{$args['perms']})
                unset($object->$prop);
            
            if(!property_exists($object, $prop) || !$object->$prop)
                continue;
            
            $oo_obj = $args['model']::get(['id'=>$object->$prop], false);
            if(!$oo_obj)
                continue;
            $this->fire($module . ':updated', $oo_obj, $oo_obj);
        }
        
        // post.slug
        // slug exists only if
        // - it's creating new post
        // - i've permission to do so
        if(isset($object->id) && !$this->can_i->update_post_slug)
            unset($object->slug);
        
        // post.status
        if(!isset($statuses[$object->status]))
            $object->status = 2;
        
        if($object->status > 2){
            if(!isset($object->publisher))
                $object->publisher = $this->user->id;
            
            if($object->status == 4){
                if(!$object->published || ($old && $old->status != 4)){
                    $object->published = date('Y-m-d H:i:s');
                    $history = 4;
                }
            }else{
                $add_schedule = strtotime($object->published);
            }
            
            if($old && $old->status == 3){
                $remove_schedule = true;
                $history = 3;
            }
        }else{
            unset($object->published);
        }
        
        // post.featured
        if(!$this->can_i->set_post_featured && property_exists($object, 'featured'))
            unset($object->featured);
        
        // post.editor_pick
        if(!$this->can_i->set_post_editorpick && property_exists($object, 'editor_pick'))
            unset($object->editor_pick);
        
        // post.content
        $post_content = $object->content;
        unset($object->content);
        
        $event = 'updated';
        if(!$id){
            $event = 'created';
            if(false === ($id = Post::create($object)))
                throw new \Exception(Post::lastError());
        }else{
            $object->updated = null;
            if(false === Post::set($object, $id))
                throw new \Exception(Post::lastError());
        }
        
        if($object_content)
            PContent::set(['content' => $post_content], $object_content->id);
        else{
            PContent::create([
                'post' => $id,
                'content' => $post_content
            ]);
        }
        
        // remove and/or create the schedule
        $schedule_name = 'post-' . $id;
        if($remove_schedule){
            if($this->worker->exists($schedule_name))
                $this->worker->remove($schedule_name);
        }
        if($add_schedule)
            $this->worker->add($schedule_name, $this->router->to('adminPostPublish', ['id'=>$id]), $add_schedule);
        
        // remove instant article content
        if(module_exists('post-facebook-instant-article-feed'))
            PFBIArticle::remove(['post'=>$id]);
        
        // insert and remove chains
        foreach($chains as $module => $args){
            $opt = $this->oo_chains[$module];
            
            if($args['remove'])
                $opt['chain']::remove([$opt['table'] => $args['remove'], 'post'=>$id]);
            
            if(!$args['insert'])
                continue;
            
            $oo_insert = [];
            foreach($args['insert'] as $oid)
                $oo_insert[] = [ 'user' => $this->user->id, 'post' => $id, $opt['table'] => $oid];
            $opt['chain']::createMany($oo_insert);
        }
        
        $this->fire('post:'. $event, $object, $old);
        
        // post history 
        PHistory::create([
            'user' => $this->user->id,
            'post' => $id,
            'type' => $history
        ]);
        
        return $this->redirect($params['ref']);
    }
    
    public function indexAction(){
        if(!$this->user->login)
            return $this->loginFirst('adminLogin');
        if(!$this->can_i->read_post)
            return $this->show404();
        
        $params = $this->_defaultParams();
        $params['reff']  = $this->req->url;
        $params['posts'] = [];
        
        $page = $this->req->getQuery('page', 1);
        $rpp  = 20;
        
        $cond = [];
        
        if(!$this->can_i->read_post_all)
            $cond['user'] = $this->user->id;
        
        $posts = Post::getX($cond, $rpp, $page, 'updated DESC');
        if($posts)
            $params['posts'] = \Formatter::formatMany('post', $posts, false, ['user', 'publisher', 'statistic']);
        
        $params['total'] = $total = Post::countX($cond);
        
        if($total > $rpp)
            $params['pagination'] = \calculate_pagination($page, $rpp, $total, 10, $cond);
        
        return $this->respond('post/index', $params);
    }
    
    public function publishAction(){
        $id = $this->param->id;
        if(!$id)
            return $this->ajax(['error' => 'Invalid ID']);
        
        $post = Post::get($id, false);
        if(!$post)
            return $this->ajax(['error' => 'Post not found']);
        
        if($post->status != 3)
            return $this->ajax(['error' => 'Post not in schedule']);
        
        $published = strtotime($post->published);
        if($published > time())
            return $this->ajax(['error' => 'Post not ready to publish']);
        
        Post::set(['status' => 4], $post->id);
        $this->fire('post:updated', $post, $post);
        
        // let tell all chains that they're updated
        foreach($this->oo_chains as $module => $args) {
            if(!module_exists($module))
                continue;
            
            $oo_chain = $args['chain']::get(['post'=>$post->id], true);
            if(!$oo_chain)
                continue;
            
            $oo_ids = array_column($oo_chain, $args['table']);
            $oo_obj = $args['model']::get(['id'=>$oo_ids], true);
            if(!$oo_obj)
                continue;
            
            foreach($oo_obj as $obj)
                $this->fire($module . ':updated', $obj, $obj);
        }
        
        // TODO
        // let tell all other single object that related to me 
        // that they're updated.
        
        $this->ajax(['error'=>false, 'data'=>'success']);
    }
    
    public function removeAction(){
        if(!$this->user->login)
            return $this->show404();
            
        $id = $this->param->id;
        $object = Post::get($id, false);
        if(!$object)
            return $this->show404();
        
        if($object->user != $this->user->id){
            if(!$this->can_i->remove_post_all)
                return $this->show404();
        }elseif(!$this->can_i->remove_post){
            if(!$this->can_i->remove_post_all)
                return $this->show404();
        }
        
        // save the data
        $trash = [$this->oo_models['post'] => $object];
        
        Post::remove($object->id);
        
        // let remove all chains objects
        foreach($this->oo_chains as $module => $args){
            if(!module_exists($module))
                continue;
            
            $oo_chain = $args['chain']::get(['post'=>$object->id], true);
            if(!$oo_chain)
                continue;
            
            $trash[$args['chain']] = $oo_chain;
            $oo_ids = array_column($oo_chain, $args['table']);
            $oo_obj = $args['model']::get(['id'=>$oo_ids], true);
            if(!$oo_obj)
                continue;
            
            foreach($oo_obj as $obj)
                $this->fire($module . ':updated', $obj, $obj);
            
            $args['chain']::remove(['post'=>$object->id]);
        }
        
        // post content 
        $post_content = PContent::get(['post'=>$object->id], false);
        if($post_content){
            $trash[$this->oo_models['post_content']] = $post_content;
            PContent::remove($post_content->id);
        }
        
        // post statistic 
        $pstat = PStatistic::get(['post'=>$object->id], false);
        if($pstat){
            $trash[$this->oo_models['post_statistic']] = $pstat;
            PStatistic::remove($pstat->id);
        }
        
        // post history 
        PHistory::create([
            'user' => $this->user->id,
            'post' => $object->id,
            'type' => 5
        ]);
        $phist = PHistory::get(['post'=>$object->id], true);
        if($phist){
            $trash[$this->oo_models['post_history']] = $phist;
            PHistory::remove(['post'=>$object->id]);
        }
        
        // post instant article, we're going to remove it
        if(module_exists('post-facebook-instant-article-feed'))
            PFBIArticle::remove(['post'=>$object->id]);
        
        // save them to trash
        $target_file = BASEPATH . '/etc/post/trash/' . $object->id . '.json';
        $f = fopen($target_file, 'w');
        fwrite($f, json_encode($trash));
        fclose($f);
        
        $this->fire('post:deleted', $object);
        $ref = $this->req->getQuery('ref');
        if($ref)
            return $this->redirect($ref);
        
        return $this->redirectUrl('adminPost');
    }
    
    public function statisticAction(){
        if(!$this->user->login)
            return $this->show404();
        if(!$this->can_i->read_post_statistic)
            return $this->show404();
        
        $params = $this->_defaultParams();
        $params['post'] = null;
        $params['histories'] = [];
        $params['statistic'] = null;
        $params['back'] = $this->req->getQuery('ref');
        
        $object = Post::get($this->param->id, false);
        if(!$object)
            return $this->show404();
        $params['post'] = $object;
        
        $stat = PStatistic::get(['post'=>$object->id], false);
        if($stat)
            $params['statistic'] = $stat;
        
        $hists = PHistory::get(['post'=>$object->id], true, false, 'created DESC');
        if($hists)
            $params['histories'] = \Formatter::formatMany('post_history', $hists, false, ['user']);
        
        return $this->respond('post/statistic', $params);
    }
}