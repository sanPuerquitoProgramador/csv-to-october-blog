<?php namespace PolloZen\Joomlacsvimport\Models;

use Model;
use DB;
use File;
use Flash;
use Hash;
use October\Rain\Support\ValidationException;
use Storage;
use Str;
use System\Classes\PluginManager;
use System\Models\File as FileModel;
use Cms\Classes\Page;
use Backend\Models\User;
use Markdown;



/**
 * joomlacsvimport Model
 */
class Joomlacsvimport extends Model
{
    /* Se require validación de datos en la BD */
    use \October\Rain\Database\Traits\Validation;

    /*Se implenentan estas cosas locales para poder acceder al form del backend sin necesidad de una tabla*/
    public $implement = ['System.Behaviors.SettingsModel'];
    public $settingsCode = 'pollozen_blogcsvimporter_setting';
    public $settingsFields = 'fields.yaml';
    public $importNowFlag;

    /* Reglas de validación (del trait/Validaion) */
    public $rules = [
        'import_csv_file' => 'required'
    ];

    /* Relaciones */
    public $attachOne = [
        'import_csv_file' => ['System\Models\File']
    ];

    /**
     * Validación de que existe RainLab Blog instalado
     * @return string
     */
    public static function getBlogVersion(){
        $plugins = array_keys(PluginManager::instance()->getPlugins());
        if(in_array('RainLab.Blog',$plugins)){
            return 'RainLab.Blog';
        } else{
            return;
        }
    }

    /**
     * Se usan para llenar los campos del plugin
     * @return array
     */
    public function getDefaultAuthorOptions(){
        return User::lists('login', 'id');
    }

    public function getBlogVersionAttribute(){
        return $this->getBlogVersion();
    }

    /**
     * AfterValidation No sé bien pa que sirve... copiado tal cual
     * @return [type] [description]
     */
    public function afterValidate(){
        $this->importNowFlag = $this->import_csv_now;
        $this->import_csv_now = 'no';
        $this->blog_version = $this->getBlogVersion();
    }

    /**
     * AfterSave, verifica que la bandera importNowFlag venga en yes y si si, manda llamar la función onDoImport
     * Lo que no sé es cuándo se manda traer y en consecuencia, no sé bien que pedo<<-- resuelto, afterSave es un método de october definido al momento de darle clic en guardar
     * @return [type] [description]
     */
    public function afterSave(){
        if(!empty($this->import_csv_file)){
            $r = $this->doItBaby();
        } else {
            Flash::success('Looks like this is the first time you save the plugin. Save again to import the contents of the CSV file');
        }
    }

     /**
     * Generate hashed folder name from filename
     *
     * @param  string
     * @return array
     */
    public static function generateHashedFolderName($filename){
        $folderName[] = substr($filename, 0, 3);
        $folderName[] = substr($filename, 3, 3);
        $folderName[] = substr($filename, 6, 3);
        return $folderName;
    }

    /**
     * Grab image from url
     * @param  string
     * @return array
     */
    public function downloadFileCurl($url){
        set_time_limit(360);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        $fileContent = curl_exec($ch);
        curl_close($ch);
        if ($fileContent) {
            return $fileContent;
        } else {
            return false;
        }
    }

    public function checkSubfolder($path){
        $folder = '/'.Joomlacsvimport::get('installation_folder').'/';
        $path = str_replace($folder, '', $path);
        return $path;
    }

    public function magicArray($filePath){
        $fields = array( 'title','slug','publish_up','category_id','category','image_url','content','excerpt');

        if(file_exists($filePath)){
            $handle = fopen($filePath, "r");
        } else {
            $r=array(FALSE,'The CSV File couldn\'t be opened. Is your October site in a subfolder?');
            return $r;
        }
        $rowControl = 0;
        while (($row = fgetcsv($handle, 0, ",", '"')) !== FALSE){
            if($rowControl == 0){
                $header = $row;
            }
            $post[] = (object) array_combine($header, $row);
            $rowControl++;
        }
        unset($post[0]);
        fclose($handle);
        // buscamos que existan los campos necesarios
        foreach($fields as $key=>$value){
            if(in_array($value, $header) != TRUE){
                $r=array(FALSE,$value.' column is not present in the CSV file');
                return $r;
            }
        }
        return $post;
    }

    public function checkRow($post){
        $fila = 0;
        foreach($post as $key=>$value){
            if($value==''){
                $fila++;
            }
        }
        return ($fila==0)?TRUE:FALSE;
    }

    /**
     * Funcion buena para hacer la importación
     * @return array
     */
    public function doItBaby(){
        $blogVersion = Joomlacsvimport::get('blog_version');
        if($blogVersion == 'RainLab.Blog'){
            $blogCategory = 'RainLab\\Blog\\Models\\Category';
            $blogPost = 'RainLab\\Blog\\Models\\Post';
        } else{
            Flash::error('Errors encountered - no blog plugin detected, please make sure you have either the blog plugin installed before you can use the importer.');
            exit();
        }
        //Default count
        $countError = 0;
        $countImport = 0;
        $tempFolder = 'storage/app/uploads/public/';

        //Publish status
        $publishStatus = Joomlacsvimport::get('publish_status');

        if(!empty($this->import_csv_file)){
            set_time_limit(360);
            $defaultAuthor = Joomlacsvimport::get('default_author');

            $csvFile = $this->import_csv_file;
            $csvFilePath = $csvFile->getPath();
            $csvFilePath = $this->checkSubfolder($csvFilePath);

            $magicArray = $this->magicArray($csvFilePath);

            if(isset($magicArray[0]) && $magicArray[0] == FALSE){
                /*
                 //esperemos a resolver el misterio del archivo, mientras pues va como success. Ni pedo...
                 */
                \Flash::success($magicArray[1]);
                // exit();
            } else {
                $emptyRows = 0;
                $processed = 0;
                $imported = 0;
                foreach($magicArray as $key => $item){
                    $row = $this->checkRow($item);
                    if($row != FALSE){
                        //Insert post.. first search the slug
                        $postBlog = $blogPost::where('slug', '=', $item->slug)->first();
                        //If post doesn't exist then create it
                        if (! $postBlog) {
                            $postBlog = $blogPost::create(['title' => $item->title, 'slug' => $item->slug, 'content' => '&nbsp;']);
                            //Now go to the category (indented for clearity purpouse only)
                                $postCategory = $blogCategory::firstOrCreate(['name' => $item->category]);
                                $postCategory->slug = Str::slug($item->category);
                                $postCategory->save();
                                $postCategory->posts()->detach($postBlog->id); //Detach if exist
                                $postCategory->posts()->attach($postBlog->id); //Attach category to post
                            //the category is created and attached to the post
                            //It's time for the user, which has been selected from the plugin Screen.
                                $postBlog->user_id = $defaultAuthor;

                            //Go back to the post... resume the data:
                            $postBlog->title = $item->title;
                            $postBlog->content = $item->content;
                            $postBlog->slug = $item->slug;
                            $postBlog->excerpt = (isset($item->excerpt)) ? $item->excerpt : "";
                            $postBlog->published_at = $item->publish_up;
                            $postBlog->published = $publishStatus;
                            $postBlog->save();
                            $imported++;

                            //and the true magis is here... the featured image THANKS YOU SO MUCH TO KLYP
                            $attachmentImage = $item->image_url;
                            $fileContents = $this->downloadFileCurl($attachmentImage);
                            if ($fileContents) {
                                $fileName = basename($attachmentImage);
                                $fileExt = File::extension($attachmentImage);

                                $hash = md5($fileName. '!' .str_random(40));
                                $diskName = base64_encode($fileName. '!' .$hash).'.'.$fileExt;
                                $fileTemp = $tempFolder.$diskName;

                                File::put($fileTemp, $fileContents);
                                $uploadFolders = $this->generateHashedFolderName($diskName);
                                $uploadFolder = $tempFolder.$uploadFolders[0].'/'.$uploadFolders[1].'/'.$uploadFolders[2];
                                File::makeDirectory($uploadFolder, 0755, true, true);

                                $fileMime = File::mimeType($fileTemp);
                                $fileSize = File::size($fileTemp);

                                $fileNew = $uploadFolder.'/'.$diskName;
                                if (File:: move($fileTemp, $fileNew)) {
                                    $postFeaturedImage = new FileModel;
                                    $postFeaturedImage->disk_name = $diskName;
                                    $postFeaturedImage->file_name = $fileName;
                                    $postFeaturedImage->file_size = $fileSize;
                                    $postFeaturedImage->content_type = $fileMime;
                                    $postFeaturedImage->field = 'featured_images';
                                    $postFeaturedImage->attachment_id = $postBlog->id;
                                    $postFeaturedImage->attachment_type = 'RainLab\Blog\Models\Post';
                                    $postFeaturedImage->is_public = 1;
                                    $postFeaturedImage->sort_order = 1;
                                    $postFeaturedImage->save();
                                }
                            }
                        }
                        $processed++;
                    } else {
                        $emptyRows++;
                    }
                }
                \Flash::success($processed.' rows has been readed '.$imported.' post has been imported and '.$emptyRows.' rows has been excluded');
            }
        }
    }
}