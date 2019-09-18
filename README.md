# UNC Catalog Plugin
Plugin that allows you to pull in course information, department information, or any page information from the official [UNC Catalog website](http://catalog.unc.edu).

This plugin uses forward proxy to get through security firewall, so it won't work on local development. See comments in the `catalog_get_xml` function.

# Documentation
## Single Course Information
This shortcode displays information about a single course.

`[catalog_course course=""]`

* **course:** the three or four letter department abbreviation and course number (separated by a space). The catalog website has a list of abbreviations for reference.

### Example
`[catalog_course course="Engl 120"]`

## Full Department Course List
This shortcode displays a full listing of a department’s courses.

`[catalog_courselist department=""]`

* **department:** the three or four letter department abbreviation

### Example
`[catalog_courselist department="Aero"]`

## Catalog Page Information
This shortcode displays information from almost any page on the catalog website.

`[catalog url="" title="show" overview="show" sections=""]`

* **url:** the url of the page to pull information from
* **title:** show or hide the title of the page. Options are show or hide. Default is show.
* **overview:** show or hide the overview section of the page. Not all pages will have an overview section, or it may not be listed as an overview section if there are no other sections. Options are show or hide. Default is show.
* **sections:** (optional) which sections (tabs) of the page to display. Common examples are requirements, sample plan, opportunities. Not all pages will have more than one section, some pages will only have an overview section. Separate sections with a comma.

### Example
`[catalog url="http://catalog.unc.edu/undergraduate/programs-study/african-american-diaspora-studies-minor/" title="show" overview="show" sections="requirements, opportunities"]`
