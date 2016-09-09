# codeigniter3_db_migreater_mysql
Theis repository will help you to run all your sqls without running them manually..

You have to manually copy and paste the files in to your project after you have downloaded the code.

SUPPORT FRAMEWORK - Codeigniter v3


DIRECTORY STRUCTURE 
-------------------
application
  |- config
      |- database.php
            | - you have to add these lines at the end of the database.php 
                  define('DATABASE_HOST',$db['default']['hostname']);
                  define('DATABASE_USER',$db['default']['username']);
                  define('DATABASE_PSW',$db['default']['password']);
                  define('DATABASE_DB',$db['default']['database']);

config - copy this folder to you project root.
  |- migration
       |- migrate.php
       |- migrate.sql
       |- readme.md
  
sql - Copy this folder inside to project root. This is the folder that you have to include your sqls. If you have phases you can have them too.
  |- phase1
        |- 2016_09_08_first_sql_.sql
        |- 2016_09_09_add_table_test.sql
            etc...
  |- phase2
        .
        .
        .
  |- phase3      

To run the migration, simply run the following command..
      php config/migration/migrate.php
      
If the command executed successfully following message should appear on your terminal.

      ##################Migration Finished : 2016-09-09 12:42:45 PM###################################
