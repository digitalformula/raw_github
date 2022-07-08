<?php
/**
 * Plugin Name: Raw Github
 * Plugin URI: https://www.digitalformula.net
 * Description: Accept a GitHub raw file URL and desired language, then display it in a post using a custom shortcode and prism.js
 * Version: 0.1
 * Text Domain: digitalformula-net-raw-github
 * Author: Chris Rasmussen
 * Author URI: https://www.digitalformula.net
 */

/**
 * custom prism.js download url
 *
 * https://prismjs.com/download.html#themes=prism&languages=markup+css+clike+javascript+aspnet+bash+basic+c+csharp+cpp+docker+fsharp+git+go    +go-module+javadoclike+jq+json+json5+jsonp+makefile+markdown+markup-templating+matlab+mongodb+nginx+perl+php+phpdoc+php-extras+powershel    l+python+jsx+regex+rest+ruby+rust+sass+scss+shell-session+sql+typescript+vbnet+vim+visual-basic+xml-doc+yaml&plugins=line-highlight+line    -numbers+show-language+toolbar+copy-to-clipboard+download-button+match-braces
 *
*/

if( !defined( 'RAWGITHUB_VER' ) )
    define( 'RAWGITHUB_VER', '1.0.0' );

if ( !class_exists( 'RawGithub_Options' ) ) {
    class RawGitHub_Options
    {

        public static function init() {
            /**
             * setup the hook that will inject the prism.js files into the site
            */
            add_action( 'wp_enqueue_scripts', 'RawGithub_Options::add_prism' );
            add_shortcode('raw_github', 'RawGithub_Options::register_shortcode');
        }

        /**
         * enqueue prism.js files
        */
        public static function add_prism() {
            # register prism.css
            wp_register_style( 'prismCSS', plugin_dir_url( __FILE__ ) . 'css/prism.css' );
            # register prism.js
            wp_register_script( 'prismJS', plugin_dir_url( __FILE__ ) . 'js/prism.js' );
            # load the previously enqueued CSS and JS
            wp_enqueue_style( 'prismCSS' );
            wp_enqueue_script( 'prismJS' );
        }

        public static function generate_container($lang = 'none', $content = 'No content specified')
        {
            $container_header = '<pre><code class="language-';
            $container_footer = '</code></pre>';
            return($container_header . $lang . '">' . $content . $container_footer);
        }

        /**
         * function that runs when the shortcode is used
        */
        public static function register_shortcode($atts = [], $content = null, $tag = "" ) {
            // convert attribute keys to lowercase
            $atts = array_change_key_case( (array) $atts, CASE_LOWER);

            /**
             * try and grab the raw code snippet from GitHub
             * if this fails, return a message informing the admin something broke
            */
            try {

                // first make sure the user has specified a GitHub url
                if(strpos($atts['url'], 'raw.githubusercontent.com')) {

                    $code = file_get_contents($atts['url']);
                    // no code snippet was returned
                    if( empty( $code ) )
                    {
                        $code = "Unable to retrieve raw code snippet.  Please verify the URL is valid.";
                        $container = RawGitHub_Options::generate_container('none', $code);
                    }
                    // GitHub returned the code snippet so format it appropriately
                    else {
                        $container = RawGitHub_Options::generate_container($atts['lang'], apply_filters('the_content', $code));
                    }
                }
                else {
                    $container = RawGitHub_Options::generate_container('none', 'This plugin is intended for GitHub raw files only.  Please make sure you have provided the URL for a GitHub-hosted raw file.');
                }
            }
            /**
             * bad to catch all exceptions like this
             * we don't know what exceptions will be thrown, though, so need to react in case something happens
            */
            catch( Exception $e) {
                # $code = '<pre><code class="language-none">An error occurred during code snippet retrieval.  Please verify the URL is valid.</code></pre>';
                $code = RawGitHub_Options::generate_container('none', 'An error occurred during code snippet retrieval.  Please verify the URL is valid.');
            }

            // arrange the attributes that will be used in the response
            $atts = shortcode_atts(
                array(
                    'code' => $code,
                    'lang' => $atts['lang']
                ), $atts, $tag
            );
            return $container;
        }

    }

    RawGithub_Options::init();

}
