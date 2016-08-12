# site_diff
# August 12, 2016

Tool to check urls for changes after server software updates

Usage:

Place list of URL's to check into text file, one per line. Entries starting with http:// or https:// are used as is. Entries
without http:// or https:// will have http:// added to them and a closing slash.

site_diff grabs a copy of the html for each url and saves it. A second pass of all URL's is performed to remove any with
dynamic content to prevent false positives when running the compare operation.

After updates or other changes are made to your server environment you should run site_diff with the -c (compare) option to
identify any URL's which have changed as a result of the server updates.

Example:

site_diff -i=urls.txt -o=output

# make changes to server, etc.

site_diff -i=urls.txt -o=output -c


