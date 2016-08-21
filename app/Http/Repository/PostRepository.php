<?php
/**
 * Created by PhpStorm.
 * User: lufficc
 * Date: 2016/8/19
 * Time: 17:41
 */
namespace App\Http\Repository;

use App\Http\Controllers\CategoryController;
use App\Post;
use App\Tag;
use Carbon\Carbon;
use Illuminate\Http\Request;
use DB;

/**
 * design for cache
 *
 *
 * Class PostRepository
 * @package App\Http\Repository
 */
class PostRepository
{

    static $tag = 'post';

    public $time = 1440;

    /**
     * @param int $page
     * @return mixed
     */
    public function pagedPostsWithoutGlobalScopes($page = 20)
    {
        $posts = cache()->tags(PostRepository::$tag)->remember('post.WithOutContent.' . $page . '' . request()->get('page', 1), $this->time, function () use ($page) {
            return Post::withoutGlobalScopes()->orderBy('published_at', 'desc')->select(['id', 'title', 'slug', 'deleted_at', 'published_at', 'status'])->paginate($page);
        });
        return $posts;
    }

    /**
     * @param int $page
     * @return mixed
     */
    public function pagedPosts($page = 7)
    {
        $posts = cache()->tags(PostRepository::$tag)->remember('post.page.' . $page . '' . request()->get('page', 1), $this->time, function () use ($page) {
            return Post::with(['tags', 'category'])->orderBy('created_at', 'desc')->paginate($page);
        });
        return $posts;
    }

    /**
     * @param $slug string
     * @return Post
     */
    public function get($slug)
    {
        $post = cache()->tags(PostRepository::$tag)->remember('post.one.' . $slug, $this->time, function () use ($slug) {
            return Post::where('slug', $slug)->with(['tags','category'])->first();
        });

        if (!$post)
            abort(404);
        return $post;
    }

    /**
     * @param Request $request
     * @return mixed
     */

    public function create(Request $request)
    {
        cache()->flush();

        $ids = [];
        $tags = $request['tags'];
        if (!empty($tags)) {
            foreach ($tags as $tagName) {
                $tag = Tag::firstOrCreate(['name' => $tagName]);
                array_push($ids, $tag->id);
            }
        }
        $status = $request->get('status', 0);
        if ($status == 1) {
            $request['published_at'] = Carbon::now();
        }

        $post = auth()->user()->posts()->create(
            $request->all()
        );
        $post->tags()->sync($ids);

        return $post;
    }

    /**
     * @param Request $request
     * @param Post $post
     * @return bool|int
     */

    public function update(Request $request, Post $post)
    {
        cache()->flush();

        $ids = [];
        $tags = $request['tags'];
        if (!empty($tags)) {
            foreach ($tags as $tagName) {
                $tag = Tag::firstOrCreate(['name' => $tagName]);
                array_push($ids, $tag->id);
            }
        }
        $post->tags()->sync($ids);

        $status = $request->get('status', 0);
        if ($status == 1) {
            $request['published_at'] = Carbon::now();
        }

        return $post->update($request->all());
    }

    /**
     * clear all cache whit tag post
     *
     */
    public function clearCache()
    {
        cache()->tags(PostRepository::$tag)->flush();
    }
}