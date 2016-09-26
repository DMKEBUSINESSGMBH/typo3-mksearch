# Solr Conf

Here you can find some example solr configurations
which are necessary to run solr through mksearch.

1. Create a new folder on the solr server at the cores folder  
(the folder who are the solr cores are created in).
2. Copy the conf folder of your solr version throu the just created folder.  
For example, use the `conf6x` folder for an installed SOLR 6.2 and rename the folder to `conf`.
3. After that copy over the `lang` folder from this directory to the just renamed `conf` folder on your solr server.  
4. At least add the new core to the solr core config or add the core by the solr admin panel.