<?php

namespace TeamTeaTime\Forum\Support\Frontend;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use TeamTeaTime\Forum\Models\Category;
use TeamTeaTime\Forum\Models\Post;
use TeamTeaTime\Forum\Models\Thread;

class Forum
{
    public static function alert(string $type, string $transKey, int $transCount = 1, array $transParameters = []): void
    {
        $alerts = Session::get('alerts', []);

        $message = trans_choice("forum::{$transKey}", $transCount, $transParameters);

        array_push($alerts, compact('type', 'message'));

        Session::flash('alerts', $alerts);
    }

    public static function render(string $content): string
    {
        return nl2br(e($content));
    }

    public static function route(string $route, $model = null, string $locale = null): string
    {
        $as = config('forum.frontend.router.as');

        if (!Str::startsWith($route, $as)) {
            $route = "{$as}{$route}";
        }

        $locale = $locale ?? app()->getLocale();

        if ($model === null) {
            return route($route, ['locale' => $locale]);
        }

        if ($model instanceof Category) {
            return route($route, [
                'locale' => $locale,
                'category_id' => $model->id,
                'category_slug' => static::slugify($model->title, 'category'),
            ]);
        }

        if ($model instanceof Thread) {
            return route($route, [
                'locale' => $locale,
                'thread_id' => $model->id,
                'thread_slug' => static::slugify($model->title),
            ]);
        }

        if ($model instanceof Post) {
            $params = [
                'locale' => $locale,
                'thread_id' => $model->thread->id,
                'thread_slug' => static::slugify($model->thread->title),
            ];

            $append = null;

            if ($route === "{$as}thread.show") {
                $params['page'] = $model->getPage();
                $append = "#post-{$model->sequence}";
            } else {
                $params['post_id'] = $model->id;
            }

            return route($route, $params) . $append;
        }

        throw new \Exception('Invalid model type passed to Forum::route().');
    }


    public static function slugify(string $string, string $fallback = 'thread'): string
    {
        $slug = Str::slug($string, '-');

        if (empty($slug)) {
            // Fall back to the supplied string - this is likely to happen with unicode titles.
            // See https://www.teamteatime.net/docs/laravel-forum/5/web/helpers/ for information
            // about replacing this method if you need something more advanced.
            return $fallback;
        }

        return $slug;
    }
}
