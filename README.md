GWVP
====

ALPHA

GWVP stands for "git over web via php". It was written entirely for myself and
its intention was to have a simple way of managing some git repos that can be
pulled and pushed to without the overhead of dealing with ssh keys. It also 
allows the owner to give permissions to a repo such that you can control who
reads and writes to it. All access to a repo is via http/https using usernames
and passwords.

Currently the code-base is a bit messy but that will improve over time.

Installation
============

Note: the installation assumes the locations for things like "git" and
git-http-backened, if yours arent in the normal ubuntu locale for that then
you will need to edit a number of files to point it at the proper location

Ubuntu
You'll need to add following packages:

```
sudo apt-get install php5-sqlite git apache2 php5-cli
```

Assuming you clone the repository into /opt/gwvp you will then need to make
some changes to the rewrite, apache and gwvp configuration (assuming /gwvp
for the url also)

#### Apache config

This goes in /etc/apache2/conf.d/gwvp.conf
```
Alias /gwvp /opt/gwvp/www

<Directory /opt/gwvp/www>
	DirectoryIndex index.php
	AllowOverride All
</Directory>
```

#### Rewrite

Edit the file /opt/gwvp/www/.htaccess and change the top line to match your
base url

```
RewriteBase /gwvp
...
```


#### GWVP Config

in /opt/gwvp/www/config.php data directory is (by default) /opt/gwvp/data
This directory must be read/write by the apache user. The following will
give the apache user write access to the file system:

```
mkdir -p /opt/gwvp/data
sudo chown www-data:www-data /opt/gwvp/data
```

Done - now you should browse to http://yourmachine/gwvp and login as admin
or user with a password of "password". Hopefully the rest is fairly straight
forward and self-explanitory, but there will be a help system coming soon(tm)
