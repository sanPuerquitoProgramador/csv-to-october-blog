<?php namespace Pollozen\Joomlacsvimport;

use Backend;
use Pollozen\Joomlacsvimport\Models\Joomlacsvimport;
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
        $plugin = Joomlacsvimport::getBlogVersion();

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
            'name'        => 'Joomla CSV Post Importer',
            'description' => 'Plugin for import content to Rainlab Blog using a Joomla CSV file. The plugin import content, create categories if needed and import a feature image using the image URL',
            'author'      => 'PolloZen',
            'icon'        => 'icon-joomla'
        ];
    }

    /**
     * RegisterSettings
     * @return array
     */
    public function registerSettings(){
        $plugin = Joomlacsvimport::getBlogVersion();
        if($plugin == 'RainLab.Blog'){
            return[
                'joomlacsvimportsettings'=>[
                    'label'       => 'Joomla CSV Importer for Blog',
                    'description' => 'Import a Joomla CSV file posts into Blog plugin.',
                    'icon'        => 'icon-joomla',
                    'class'       => 'Pollozen\joomlacsvimport\Models\Joomlacsvimport',
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
