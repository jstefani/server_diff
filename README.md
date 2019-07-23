site_diff

site_diff is a tool to check large numbers of urls for differences after changes to server components. site_diff is not
intended for ongoing monitoring of website changes (there are better tools for this). Instead, it is meant to monitor for
unintentional errors introduced by upgrades of server software. Projects with large numbers of software dependencies are
vulnerable to unexpected issues from even minor software version and configuration changes.

Usage:

Create a text file with a list of domain names or URLS, one per line. Entries starting with http:// or https:// are used as 
is. Entries without http:// or https:// will have http:// added to the front of the line and a closing slash added to the
end of the line.

site_diff retrieves a copy of the html for each url and saves it. A second pass of all URL's is performed to remove any URL's
with dynamic content to prevent false positives. No further action is currently taken on these URLs.

After updates or other changes are made to your server environment, run site_diff with the -c (compare) option to
identify any URL's which have changed as a result of the server updates.

Example:

1. Retrieve current state of all URL's

```site_diff -i=urls.txt -o=output```

2. Make changes to server - upgrade software, etc.

3. Compare stored copy to current live version of all URL's

	site_diff -i=urls.txt -o=output -c

The -r or --ip option causes site_diff to skip any URL which does not resolve to the IP address given. This is useful for 
scanning large lists of URL's which contain entries that are not of interest.

Additional Notes:

You may use the following to quickly retrieve all domains on a local webserver :

  grep ServerName /etc/httpd/conf/httpd.conf | cut -d" " -f 2 > domains.txt

A better approach is to capture a list of actual URL's in wide usage on your server or group of servers. This will ensure
you are testing actual active content instead of simply the index page of the domain.


