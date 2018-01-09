<?php

namespace WordPressMetasearch;

/**
 * Metasearch Class
 *
 */
class Metasearch
{

    /**
     * Query
     * @var array
     */
    private $query = [
        'post'      => [],
        'post_type' => [],
        'parent'    => [],
        'taxonomy'  => [],
        'template'  => [],
        'slug'      => '',
        'wp_args'   => [],
    ];


    /**
     * Fields
     * @var array
     */
    private $meta = [];


    /**
     * Posts
     * @var array
     */
    private $posts = [];


    /**
     * Results
     * @var array
     */
    private $results = [];


    /**
     * Construct
     * @param  array $args
     *
     */
    public function __construct($args = [])
    {
        // Check if shortcut arguments are set.
        if (empty($args) || (empty($args['meta']) && empty($args['query']))) {
            return;
        }

        // Set query if argument query are set in shortcut arguments.
        if (!empty($args['query']) && is_array($args['query'])) {
            $this->setQuery($args['query']);
        }

        // Set meta if argument meta are set in shortcut arguments.
        if (!empty($args['meta']) && is_array($args['meta'])) {
            $this->setMeta($args['meta']);
        }

        // Iniitate shortcut setup.
        $this->init();
    }


    /**
     * Set Query
     * @param  array $args
     *
     */
    public function setQuery($args = [])
    {
        // Check if arguments are empty.
        if (empty($args)) {
            return;
        }

        // Set metabox object settings.
        $this->query = array_merge($this->query, $args);
    }


    /**
     * Set Fields
     * @param  array $args
     *
     */
    public function setMeta($args = [])
    {
        // Check if arguments are empty.
        if (empty($args)) {
            return;
        }

        // $_GET - Specific Meta Field.
        if (!empty($_GET['metakey']) && !empty($_GET['action'])) {
            // Update
            if ($_GET['action'] == 'update') {
                $this->meta = [
                    $_GET['metakey'] => [
                        $_GET['metavalue'],
                        $_GET['value']
                    ]
                ];
                return;
            }

            // Remove
            if ($_GET['action'] == 'remove') {
                $this->meta = array($_GET['metakey'] => $_GET['metavalue']);
                return;
            }
        }

        // Check if single value, convert into array.
        if (!is_array($args)) {
            $this->meta = [$args => '*'];
            return;
        }

        // Set if numberic, set keys & wildcard.
        foreach ($args as $key => $value) {
            // Set wildcard selector.
            if (is_numeric($key) && !is_array($value)) {
                $this->meta[$value] = '*';
                continue;
            }

            // Build meta array.
            $this->meta[$key] = $value;
        }
    }


    /**
     * Init
     *
     */
    public function init()
    {
        // Check for needed `?meta=1` and logged in to even run.
        if (empty($_GET['meta']) || !current_user_can('administrator')) {
            return;
        }

        // Get result posts.
        $this->getPosts();

        // Update/Removal Database call.
        if (!empty($_GET['id']) && !empty($_GET['action'])) {
            $this->updateDatabase($_GET['action']);
            return;
        }

        // Show markup.
        $this->showMarkup();
    }


    /**
     * Get posts by defined query & meta.
     *
     */
    public function getPosts()
    {
        // Query results.
        $query = new \WP_Query($this->setArguments());

        // Pluck only IDs from results in array.
        $this->posts = wp_list_pluck($query->posts, 'post_title', 'ID');
    }


    /**
     * Setup Arguments.
     *
     */
    private function setArguments()
    {
        // Argument defaults.
        $arguments = [
            'post_type'      => 'any',
            'post_status'    => 'any',
            'orderby'        => 'title',
            'order'          => 'ASC',
            'posts_per_page' => -1,
        ];

        // Set custom WP arguments.
        if (!empty($this->query['wp_args']) && is_array($this->query['wp_args'])) {
            $arguments = array_merge($arguments, $this->query['wp_args']);
        }

        // Get post by slug
        if (!empty($this->query['slug'])) {
            $arguments['name'] = $this->query['slug'];
        }

        // Get posts, this trumps everything else.
        if (!empty($this->query['post'])) {
            $arguments['post__in'] = self::helperToArray($this->query['post']);
        }

        // Get posts in certain post types.
        if (!empty($this->query['post_type'])) {
            $arguments['post_type'] = self::helperToArray($this->query['post_type']);
        }

        // Get children posts with parent post.
        if (!empty($this->query['parent'])) {
            $arguments['post_parent__in'] = self::helperToArray($this->query['parent']);
        }

        // Get post with taxonomy terms.
        if (!empty($this->query['taxonomy'])) {
            $taxonomyQuery = [];

            foreach ($this->query['taxonomy'] as $taxomony => $terms) {
                $termCheck       = is_array($terms) ? $terms[0] : $terms;
                $taxonomyQuery[] = [
                    'taxonomy' => $taxomony,
                    'field'    => is_numeric($termCheck) ? 'term_id' : 'slug',
                    'terms'    => $terms,
                ];
            }

            $arguments['tax_query'] = $taxonomyQuery;
            $arguments['tax_query']['relation'] = 'OR';
        }

        // Get post assigned to template.
        if (!empty($this->query['template'])) {
            $templates      = self::helperToArray($this->query['template']);
            $templatesArray = [];
            $templatesArray['relation'] = 'OR';

            foreach ($templates as $template) {
                $templatesArray[] = [
                    'key'     => '_wp_page_template',
                    'value'   => $template,
                    'compare' => 'LIKE',
                ];
            }

            $arguments['meta_query'][] = $templatesArray;
        }

        // Get post by meta meta speicified.
        if (!empty($this->meta)) {
            $metaArray['relation'] = 'OR';

            // Loop defined meta meta.
            foreach ($this->meta as $key => $value) {
                $value     = is_array($value) ? $value[0] : $value;
                $metaArray = ['key' => $key];

                if ($value != '*') {
                    $metaArray = array_merge($metaArray, [
                        'value'   => $value,
                        'compare' => '=',
                    ]);
                }

                $arguments['meta_query'][] = $metaArray;
            }

            $arguments['meta_query']['relation'] = 'OR';
        }

        // $_GET - Specific post, if selected to remove/update.
        if (!empty($_GET['id']) && is_numeric($_GET['id'])) {
            $arguments['page_id'] = $_GET['id'];

            unset($arguments['post__in']);
        }

        // Return arguments.
        return $arguments;
    }


    /**
     * Update Database Results, whether its removal or update of rows.
     *
     */
    public function updateDatabase($action)
    {
        // Only specific actions allowed.
        if ($action != 'remove' && $action != 'update') {
            return;
        }

        // Declare $wpdb global variable.
        global $wpdb;

        // Loop defined posts.
        foreach ($this->posts as $post => $title) {
            // Loop defined meta meta.
            foreach ($this->meta as $key => $value) {
                // Get value, whether its in an update array or individual string value.
                $metaValue = is_array($value) ? $value[0] : $value;
                $result    = false;

                // Where args.
                $where = [
                    'post_id'    => $post,
                    'meta_key'   => $key,
                ];

                // If specific, update where arg.
                if ($metaValue != '*') {
                    $where = array_merge($where, ['meta_value' => $metaValue]);
                }

                // Delete.
                if ($action == 'remove') {
                    $result = $wpdb->delete(
                        $wpdb->postmeta,
                        $where
                    );
                }

                // Update.
                if ($action == 'update' && is_array($value)) {
                    $result = $wpdb->update(
                        $wpdb->postmeta,
                        ['meta_value' => $value[1]],
                        $where
                    );
                }

                // If row effected, add to results list.
                if ($result) {
                    $this->results[$post][$key] = $value;
                }
            }
        }

        // Show markup.
        $this->showMarkup();
    }


    /**
     * Show Markup
     *
     */
    public function showMarkup()
    {

        // Inline styles.
        echo file_get_contents(dirname(__FILE__). '/assets/styles.css');

        // Inline scripts.
        echo '<script>';
        echo file_get_contents(dirname(__FILE__). '/assets/scripts.js');
        echo '</script>';

        // Dashboard view.
        include dirname(__FILE__) . '/views/dashboard.php';

        // Halt everything else.
        die();
    }


    /**
     * Helper - Convert to Array
     *
     */
    public static function helperToArray($value)
    {

        if (empty($value)) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }
}
