#Migration plugin from CSV file to Rain Lab Blog

Plugin for import a CSV file with featured images (url's) to Rainlab Blog using a Joomla CSV file. The plugin import content, create categories if needed and import a feature image using the image URL. Usesful to migrate from Joomla or Wordpress to OctoberCMS

###Pre requisites
You will need a CSV file which contains the next structure:

| title|slug|publish_up|category_id|category|image_url|content|excerpt|
|-------------|-------------|--------------------------|-------------|---------------|-------------------------------------|------------------------------|---------------------|
| Lorem Ipsum | lorem-ipsum | 01/11/2016,12:06:08 a.m. | 1 | Category Name | http://www.test.com/im.jpg | Lorem Ipsum Dolorem Sit amet | Lorem Ipsum Dolorem |
*The columns order is not relevant. The really important thing is the name of columns and all the columns are required*

**The file can be created using some Joomla plugin or you can use the freemium service  [Joomla 2 CSV](http://joomla2csv.bambu.ninja) **

###Step 1
Install this pretty plugin, is free! XD, then, go to the **October Backend - Settings**. There you will find the **Joomla CSV Importer for Blog**

Fill the required fields:

**Plugin Version:** This field is not writeable. For the moment, only RainLab Blog is supported

**October Installation Folder:** If your October site is in a subfolder, please, specify here. E.g. *www.octobersite.com* do not need fill this field; *www.superhost.com/**my_october_site**/* need specify "my_october_site" as the installation folder

**Upload your CSV file: ** Remember the structure required

**Default author: ** Specify who will be the author for the imported posts

**The publish status for the imported post will be ...** You can choose published or unpublished

Your are ready! Click on **Save** and that's it!

The post data will be imported to the RainLab Blog, if the post category doesn't exists, the plug in will create it and the **image_url** will be grabbed and assigned to the post as featured image. Cool!

Additional Information:
- The import process take a while, because the plugin grabs the image_url and save it in your server, so be patient, take a coffee, drink a beer and let the plug in do the magic.
- The Content is HTML Stripped. Just the <iframe> and <a> tags are conserved
- The Excerpt is fully HTML Stripped
- If a column is missing in the CSV file, the import process will not start.
- If the plugin found a row with empty fields, this row will be ignored. If need it, you can use the "Validate CSV" option in the freemium service  [Joomla 2 CSV](http://joomla2csv.bambu.ninja)