# Composer Setup
```
"require": {
    "wordpressmetasearch": "dev-master"
},
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/jasoncomes/WordpressMetasearch"
    }
],
```

Install Meta Library project by using composer:
```
composer install
```

To Update:
```
composer update
```

If Composer is not isntall on your machine, run:
```
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
```

Setup Autoloader in `functions.php`:
```
require_once ABSPATH . '../../vendor/autoload.php';
```

# Metasearch Class
Used to retrieve meta fields with defined search query/fields. **Requirements to Use:** Logged into Wordpress as an Administrator and add `?meta=1` to the domain url, this brings up the Dashboard once you iniialize class below. 

### Initialize
```
use WordPressMetasearch\Metasearch;

$metasearch = new Metasearch;
$metasearch->meta = [
    ...Meta
];
$metasearch->query = [
    ...Query
];
$metasearch->build();
```
## Meta
```
[
    'metakey',
    'metakey' => '3',
    'metakey' => ['searchValue', 'replaceableValue'],
    'metakey' => ['2', '5'],
    'metakey' => ['1', '5'],
    'metakey'
)
```
#### Single Key: `'metakey'`
Pulls in posts with attached `metakey`, no matter the value. Basically equals this `metakey => *`. You may also do this. `'metakey' => '*'`

#### Key with Value: `'metakey' => 'value'` 
Pulls in posts where `metakey` equals `value`. You may also look for metakeys that exists but have no value. `'metakey' => ''`

#### Key with Array Value: `'metakey' => ['searchValue', 'replaceableValue']` 
Pulls in posts like above using the `searchValue`. This allows you to replace `metakey` values from the dashboard.


## Query
```
[
    'post'      => [],
    'post_type' => [],
    'parent'    => [],
    'taxonomy'  => ['taxonomy' => ['term', 'term']],
    'template'  => [],
    'slug'      => '',
    'wp_args'   => Refer to: https://codex.wordpress.org/Class_Reference/WP_Query,
)
```
##### post
(string|array) (Optional) Specific posts entered into array. e.g. `['242', '234', '12']`

##### post_type
(string|array) (Optional) Accepts a single post type or an array of post types. Default is all post types.

##### parent
(string|array) (Optional) Only children posts of specific parent post(s) entered into array. e.g. `['242')`

##### taxonomy
(array) (Optional) Only terms that are specified in taxonomies. e.g. `['category' => 'rankings']` or `['category' => 'rankings', 'post_tag' => 'schools']` or `['category' => ['rankings', 'schools'], 'post_tag' => 'schools']`

##### template
(string|array) (Optional) Only terms that are specified in template. e.g. `['front-page.php', 'contact-page.php']`

##### slug
(string) (Optional) Only page specified by slug. e.g. `'page-slug-name'`

##### wp_args
(array) (Optional) Refer to $args for WP_Query: https://codex.wordpress.org/Class_Reference/WP_Query.


#### Example Usage
```
$metasearch = new Meta;
$metasearch->meta = [
    'degree_level_id' => 2,
    'editorial_only_page' => [0, 2]
];
$metasearch->query = [
    'post'      => ['5474', '6505', '6504', '6503', '234234322'],
    'post_type' => ['post'],
    'parent'    => ['172'],
    'taxonomy'  => ['category' => ['college-rankings')],
    'template'  => ['page-banner.php'],
    'slug'      => 'page-slug-name',
    'wp_args'   => ['category__in' => [5, 6]],
];
$metasearch->build();
```