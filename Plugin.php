<?php namespace Pollozen\Blogcsvimport;

use Backend;
use Pollozen\Blogcsvimport\Models\Blogcsvimport;
use System\Classes\PluginBase;
use System\Clases\PluginManager;

/**
 * blogcsvimport Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Dependencias del plugin, se arman al vuelo
     * **La neta no sé bien que pedo
     * @var array
     */
    public $require = [];

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot(){
        //Defaults
        $blogPost = '';
        $plugin = Blogcsvimport::getBlogVersion();

        //CheckPlugin
        if($plugin == 'Rainlab.Blog'){
            $blogPost = 'RainLab\\Blog\\Models\\Post';
        }

        /* Se sobreescriben los filleables */
        if($blogPost){
            $blogPost::extend(function($model){
                $model->fillable(['user_id', 'title', 'slug', 'excerpt', 'content', 'published_at', 'published']);
            });
        }
    }

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Blog CSV Post Importer',
            'description' => 'Plugin for import content to Rainlab Blog using a CSV file. The plugin import content, create categories if needed and import a feature image using the image URL',
            'author'      => 'PolloZen',
            'icon'        => 'icon-leaf'
        ];
    }

    /**
     * RegisterSettings
     * @return array
     */
    public function registerSettings(){
        $plugin = Blogcsvimport::getBlogVersion();
        if($plugin == 'RainLab.Blog'){
            return[
                'blogcsvimportsettings'=>[
                    'label'       => 'CSV Importer for Blog',
                    'description' => 'Import CSV file posts into Blog plugin.',
                    'icon'        => 'icon-download',
                    'class'       => 'Pollozen\blogcsvimport\Models\Blogcsvimport',
                    'order'       => 1
                ]
            ];
        }
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents(){
        return [];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions(){
        return [
            'pollozen.pollozen.access_component_menu' => ['tab' => 'Component Section','label' => 'Access to Component Section'],
            'pollozen.blogcsvimport.access_blogcsvimport' => ['tab' => 'Component Section','label' => 'Access to CSV Importer Plugin']
        ];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation(){
        return [];
    }
}
