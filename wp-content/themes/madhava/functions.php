<?php

/**
 * Timber starter-theme
 * https://github.com/timber/starter-theme
 *
 * Combined and organized functions.php from user input.
 */

// Load Composer dependencies and use the Timber namespace.
use Timber\Timber;

require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/src/StarterSite.php';

// Initialize Timber.
Timber::init();

// Sets the directories (inside your theme) to find .twig files.
Timber::$dirname = ['templates', 'views'];

// Instantiate the custom starter site class.
new StarterSite();

// --------------------------------------------------------------------------
// 🔥 SUPPRESS STRAY PAGINATION OUTPUT 🔥
// --------------------------------------------------------------------------
/**
 * Suppress default WordPress pagination output.
 * This prevents the core WP pagination functions from printing HTML (like the '1')
 * before the Timber template renders its own pagination block.
 */
add_filter('navigation_markup_template', function () {
    return '';
}, 10, 2);
// --------------------------------------------------------------------------


// --------------------------------------------------------------------------
// ENQUEUE SCRIPTS AND STYLES
// --------------------------------------------------------------------------
add_action('wp_enqueue_scripts', function () {
    // 1. Loads the theme's style.css (REQUIRED if Tailwind is compiled into it)
    wp_enqueue_style('theme-style', get_stylesheet_uri());

    // Load compiled Tailwind CSS
    wp_enqueue_style('tailwind-css', get_template_directory_uri() . '/dist/output.css', array(), '1.0');
});
// --------------------------------------------------------------------------


// --------------------------------------------------------------------------
// WIDGET REGISTRATION FOR BLOG SIDEBAR
// --------------------------------------------------------------------------
/**
 * Register a dynamic sidebar area for the blog layout.
 */
function madhava_widgets_init()
{
    register_sidebar(array(
        'name'          => 'Blog Sidebar',
        'id'            => 'blog-sidebar', // The ID used in archive-blog.twig
        'description'   => 'Widgets placed here will appear on the blog archive and single post pages, next to the main content.',
        'before_widget' => '<div id="%1$s" class="widget %2$s p-6 bg-white rounded-lg shadow-md mb-6">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">',
        'after_title'   => '</h3>',
    ));
}
add_action('widgets_init', 'madhava_widgets_init');

add_filter('timber/context', function ($context) {
    if (is_single()) {
        $context['recent_posts'] = Timber::get_posts([
            'post_type' => 'post',
            'posts_per_page' => 5,
            'post__not_in' => [get_the_ID()],
        ]);
    }
    return $context;
});

function madhava_set_post_views($postID)
{
    $count_key = 'views';
    $count = get_post_meta($postID, $count_key, true);
    if ($count == '') {
        $count = 0;
        delete_post_meta($postID, $count_key);
        add_post_meta($postID, $count_key, '1');
    } else {
        $count++;
        update_post_meta($postID, $count_key, $count);
    }
}

function madhava_track_post_views($post_id)
{
    if (!is_single()) return;
    if (empty($post_id)) {
        global $post;
        $post_id = $post->ID;
    }
    madhava_set_post_views($post_id);
}
add_action('wp_head', 'madhava_track_post_views');

add_filter('timber/context', function ($context) {
    if (is_home() || is_archive() || is_category() || is_tag()) {
        $context['popular_posts'] = Timber::get_posts([
            'post_type'      => 'post',
            'posts_per_page' => 5,
            'orderby'        => 'meta_value_num',
            'meta_key'       => 'views', // Make sure you are tracking views in this meta key
            'order'          => 'DESC',
        ]);
    }
    return $context;
});

// Enqueue Tailwind & styles
function madhava_tailwind_login_enqueue() {
    ?>
    <style type="text/css">
        /* Base body styling */
        body.login {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            margin: 0;
        }

        /* Main wrapper container */
        .login-wrapper {
            width: 100%;
            max-width: 1000px;
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 600px;
        }

        /* Left illustration panel */
        .login-left {
            background-image: url('<?php echo esc_url( get_stylesheet_directory_uri() . "/static/assets/images/jagannathji.jpg" ); ?>');
            background-size: cover;
            background-position: center;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Add overlay for better readability if needed */
        .login-left::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255,152,0,0.1) 0%, rgba(255,87,34,0.1) 100%);
        }

        /* Right form panel */
        .login-right {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px 50px;
            background: #ffffff;
        }

        /* Hide default WordPress logo */
        #login h1 {
            display: none;
        }

        /* Custom heading */
        #loginform::before {
            content: 'Welcome Back';
            display: block;
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
            text-align: center;
        }

        #loginform::after {
            content: 'Login to continue your journey';
            display: block;
            font-size: 15px;
            color: #7f8c8d;
            margin-bottom: 35px;
            text-align: center;
        }

        /* Form labels */
        #loginform label {
            color: #34495e;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            display: block;
        }

        /* Input fields */
        #loginform input[type="text"],
        #loginform input[type="password"] {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            color: #2c3e50;
            font-size: 15px;
            padding: 14px 16px;
            width: 100%;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        #loginform input[type="text"]:focus,
        #loginform input[type="password"]:focus {
            background: #ffffff;
            border-color: #ff6b6b;
            outline: none;
            box-shadow: 0 0 0 4px rgba(255, 107, 107, 0.1);
        }

        /* Remember me checkbox */
        #loginform .forgetmenot {
            margin-bottom: 25px;
        }

        #loginform input[type="checkbox"] {
            margin-right: 8px;
            accent-color: #ff6b6b;
        }

        #loginform label[for="rememberme"] {
            display: inline;
            font-size: 14px;
            color: #7f8c8d;
            font-weight: 400;
        }

        /* Submit button */
        #loginform input[type="submit"] {
            background: linear-gradient(135deg, #ff6b6b 0%, #ff8e53 100%);
            border: none;
            border-radius: 12px;
            color: #ffffff;
            font-size: 16px;
            font-weight: 600;
            padding: 14px 24px;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }

        #loginform input[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
        }

        #loginform input[type="submit"]:active {
            transform: translateY(0);
        }

        /* Navigation links */
        .login #nav,
        .login #backtoblog {
            text-align: center;
            margin-top: 20px;
            padding: 0;
        }

        #nav a,
        #backtoblog a {
            color: #ff6b6b;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        #nav a:hover,
        #backtoblog a:hover {
            color: #ff8e53;
        }

        /* Error messages */
        #login_error,
        .message {
            border-left: 4px solid #ff6b6b;
            background: #fff5f5;
            padding: 12px 16px;
            margin: 0 0 20px 0;
            border-radius: 8px;
            font-size: 14px;
        }

        .message {
            border-left-color: #51cf66;
            background: #f0fdf4;
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .login-wrapper {
                grid-template-columns: 1fr;
                max-width: 450px;
            }

            .login-left {
                display: none;
            }

            .login-right {
                padding: 40px 30px;
            }

            #loginform::before {
                font-size: 28px;
            }
        }

        /* Additional styling for better visual hierarchy */
        #loginform {
            position: relative;
        }

        /* Style for password visibility toggle if exists */
        .wp-hide-pw button {
            background: transparent;
            border: none;
            color: #7f8c8d;
            cursor: pointer;
        }

        .wp-hide-pw button:hover {
            color: #ff6b6b;
        }
    </style>
    <?php
}
add_action('login_enqueue_scripts', 'madhava_tailwind_login_enqueue');

// Wrap actual login form content
function madhava_login_wrap_start() {
    ob_start();
}
add_action('login_header', 'madhava_login_wrap_start', 0);

function madhava_login_wrap_end() {
    $content = ob_get_clean();

    // Output our wrapper markup around the form
    echo '<div class="login-wrapper">';
    echo '<div class="login-left"></div>';
    echo '<div class="login-right">';
    echo $content; // original WP login HTML
    echo '</div></div>';
}
add_action('login_footer', 'madhava_login_wrap_end', 999);

// Optional: Change the login logo URL to your homepage
function madhava_login_logo_url() {
    return home_url();
}
add_filter('login_headerurl', 'madhava_login_logo_url');

// Optional: Change the login logo title
function madhava_login_logo_url_title() {
    return get_bloginfo('name');
}
add_filter('login_headertext', 'madhava_login_logo_url_title');

/**
 * Custom WordPress User Avatar System
 * Add to your theme's functions.php
 */

// 1. Add custom avatar field to user profile
function custom_avatar_field($user) {
    $avatar_url = get_user_meta($user->ID, 'custom_avatar', true);
    ?>
    <h3><?php _e('Custom Profile Picture', 'your-theme'); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="custom_avatar"><?php _e('Profile Picture', 'your-theme'); ?></label></th>
            <td>
                <div class="custom-avatar-preview-wrapper">
                    <?php if ($avatar_url) : ?>
                        <img src="<?php echo esc_url($avatar_url); ?>" class="custom-avatar-preview" style="max-width: 150px; height: auto; border-radius: 50%; margin-bottom: 10px;" />
                    <?php else : ?>
                        <img src="<?php echo esc_url(get_avatar_url($user->ID)); ?>" class="custom-avatar-preview" style="max-width: 150px; height: auto; border-radius: 50%; margin-bottom: 10px;" />
                    <?php endif; ?>
                </div>
                
                <input type="hidden" id="custom_avatar" name="custom_avatar" value="<?php echo esc_attr($avatar_url); ?>" />
                
                <button type="button" class="button avatar-image-upload">
                    <?php echo $avatar_url ? __('Change Picture', 'your-theme') : __('Upload Picture', 'your-theme'); ?>
                </button>
                
                <?php if ($avatar_url) : ?>
                    <button type="button" class="button avatar-image-remove"><?php _e('Remove Picture', 'your-theme'); ?></button>
                <?php endif; ?>
                
                <p class="description"><?php _e('Upload a custom profile picture. Recommended size: 300x300 pixels.', 'your-theme'); ?></p>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'custom_avatar_field');
add_action('edit_user_profile', 'custom_avatar_field');

// 2. Enqueue Media Uploader scripts
function custom_avatar_admin_scripts() {
    $screen = get_current_screen();
    if ($screen->id !== 'profile' && $screen->id !== 'user-edit') {
        return;
    }
    
    wp_enqueue_media();
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var file_frame;
        
        $(document).on('click', '.avatar-image-upload', function(e) {
            e.preventDefault();
            var $button = $(this);
            
            if (file_frame) {
                file_frame.open();
                return;
            }
            
            file_frame = wp.media({
                title: 'Select or Upload Profile Picture',
                library: { type: 'image' },
                button: { text: 'Use as Profile Picture' },
                multiple: false
            });
            
            file_frame.on('select', function() {
                var attachment = file_frame.state().get('selection').first().toJSON();
                var avatarUrl = attachment.sizes && attachment.sizes.thumbnail 
                    ? attachment.sizes.thumbnail.url 
                    : attachment.url;
                
                $('#custom_avatar').val(avatarUrl);
                $('.custom-avatar-preview').attr('src', avatarUrl);
                $button.text('Change Picture');
                
                if (!$('.avatar-image-remove').length) {
                    $button.after('<button type="button" class="button avatar-image-remove">Remove Picture</button>');
                }
            });
            
            file_frame.open();
        });
        
        $(document).on('click', '.avatar-image-remove', function(e) {
            e.preventDefault();
            $('#custom_avatar').val('');
            $('.custom-avatar-preview').attr('src', '<?php echo esc_js(get_avatar_url(0)); ?>');
            $('.avatar-image-upload').text('Upload Picture');
            $(this).remove();
        });
    });
    </script>
    <style>
        .custom-avatar-preview-wrapper { margin-bottom: 10px; }
        .avatar-image-remove { margin-left: 10px; }
    </style>
    <?php
}
add_action('admin_footer-profile.php', 'custom_avatar_admin_scripts');
add_action('admin_footer-user-edit.php', 'custom_avatar_admin_scripts');

// 3. Save custom avatar
function save_custom_avatar_field($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    
    if (isset($_POST['custom_avatar'])) {
        $avatar_url = sanitize_text_field($_POST['custom_avatar']);
        
        if (!empty($avatar_url)) {
            update_user_meta($user_id, 'custom_avatar', esc_url_raw($avatar_url));
        } else {
            delete_user_meta($user_id, 'custom_avatar');
        }
    }
}
add_action('personal_options_update', 'save_custom_avatar_field');
add_action('edit_user_profile_update', 'save_custom_avatar_field');

// 4. Override avatar URL
function custom_get_avatar_url($url, $id_or_email, $args) {
    $user_id = null;
    
    if (is_numeric($id_or_email)) {
        $user_id = (int) $id_or_email;
    } elseif (is_string($id_or_email)) {
        $user = get_user_by('email', $id_or_email);
        if ($user) {
            $user_id = $user->ID;
        }
    } elseif (is_object($id_or_email)) {
        if (!empty($id_or_email->user_id)) {
            $user_id = (int) $id_or_email->user_id;
        } elseif (!empty($id_or_email->ID)) {
            $user_id = (int) $id_or_email->ID;
        }
    }
    
    if (empty($user_id)) {
        return $url;
    }
    
    $custom_avatar = get_user_meta($user_id, 'custom_avatar', true);
    
    if (!empty($custom_avatar)) {
        return esc_url($custom_avatar);
    }
    
    return $url;
}
add_filter('get_avatar_url', 'custom_get_avatar_url', 10, 3);

// 5. Override avatar HTML
function custom_get_avatar($avatar, $id_or_email, $size, $default, $alt) {
    $custom_url = custom_get_avatar_url('', $id_or_email, array());
    
    if ($custom_url) {
        $avatar = sprintf(
            '<img alt="%s" src="%s" class="avatar avatar-%d photo" height="%d" width="%d" />',
            esc_attr($alt),
            esc_url($custom_url),
            (int) $size,
            (int) $size,
            (int) $size
        );
    }
    
    return $avatar;
}
add_filter('get_avatar', 'custom_get_avatar', 10, 5);






// --------------------------------------------------------------------------
// CERTIFICATE VERIFICATION SYSTEM
// --------------------------------------------------------------------------

// Load Composer's autoloader for QR Code library (already done at top, but ensure it's there)
// require_once __DIR__ . '/vendor/autoload.php';

/**
 * Register Certificate Custom Post Type
 */
function create_certificate_post_type() {
    register_post_type('certificate',
        array(
            'labels' => array(
                'name' => __('Certificates', 'madhava'),
                'singular_name' => __('Certificate', 'madhava'),
                'add_new' => __('Add New Certificate', 'madhava'),
                'add_new_item' => __('Add New Certificate', 'madhava'),
                'edit_item' => __('Edit Certificate', 'madhava'),
                'new_item' => __('New Certificate', 'madhava'),
                'view_item' => __('View Certificate', 'madhava'),
                'search_items' => __('Search Certificates', 'madhava'),
                'not_found' => __('No certificates found', 'madhava'),
                'not_found_in_trash' => __('No certificates found in Trash', 'madhava'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'supports' => array('title', 'editor'),
            'menu_icon' => 'dashicons-awards',
            'menu_position' => 20,
            'has_archive' => false,
            'publicly_queryable' => false,
            'exclude_from_search' => true,
        )
    );
}
add_action('init', 'create_certificate_post_type');

/**
 * Add Certificate Meta Boxes
 */
function add_certificate_meta_boxes() {
    add_meta_box(
        'certificate_details',
        __('Certificate Details', 'madhava'),
        'certificate_meta_box_callback',
        'certificate',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_certificate_meta_boxes');

/**
 * Certificate Meta Box Callback - Enhanced with QR Code Display
 */
function certificate_meta_box_callback($post) {
    wp_nonce_field('save_certificate_meta', 'certificate_meta_nonce');
    
    $cert_id = get_post_meta($post->ID, 'certificate_id', true);
    $recipient = get_post_meta($post->ID, 'recipient_name', true);
    $course = get_post_meta($post->ID, 'course_name', true);
    $issue_date = get_post_meta($post->ID, 'issue_date', true);
    $duration = get_post_meta($post->ID, 'duration', true);
    $description = get_post_meta($post->ID, 'description', true);
    
    ?>
    <style>
        .certificate-meta-field { margin-bottom: 20px; }
        .certificate-meta-field label { 
            display: block; 
            font-weight: 600; 
            margin-bottom: 5px; 
            color: #23282d;
        }
        .certificate-meta-field input[type="text"],
        .certificate-meta-field input[type="date"],
        .certificate-meta-field input[type="number"],
        .certificate-meta-field textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .certificate-meta-field textarea {
            min-height: 100px;
        }
        .qr-code-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
            color: white;
        }
        .qr-code-section h4 {
            margin-top: 0;
            color: white;
            font-size: 18px;
        }
        .qr-code-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin-top: 15px;
        }
        .qr-code-container h5 {
            color: #1e293b;
            margin-top: 0;
        }
        .qr-code-container img {
            max-width: 100%;
            border: 3px solid #667eea;
            border-radius: 8px;
            padding: 10px;
            background: white;
        }
        .qr-code-info {
            margin-top: 15px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            font-size: 13px;
        }
        .qr-code-url {
            word-break: break-all;
            background: rgba(0, 0, 0, 0.2);
            padding: 8px;
            border-radius: 4px;
            margin-top: 8px;
            font-family: monospace;
        }
        .download-qr-btn {
            display: inline-block;
            margin-top: 10px;
            padding: 10px 20px;
            background: #10b981;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .download-qr-btn:hover {
            background: #059669;
            transform: translateY(-2px);
        }
    </style>
    
    <div class="certificate-meta-field">
        <label for="certificate_id"><?php _e('Certificate ID *', 'madhava'); ?></label>
        <input type="text" id="certificate_id" name="certificate_id" 
               value="<?php echo esc_attr($cert_id); ?>" 
               placeholder="e.g., CERT2025001" required />
        <p class="description"><?php _e('Unique certificate identifier (required)', 'madhava'); ?></p>
    </div>
    
    <div class="certificate-meta-field">
        <label for="recipient_name"><?php _e('Recipient Name *', 'madhava'); ?></label>
        <input type="text" id="recipient_name" name="recipient_name" 
               value="<?php echo esc_attr($recipient); ?>" 
               placeholder="e.g., John Doe" required />
    </div>
    
    <div class="certificate-meta-field">
        <label for="course_name"><?php _e('Course/Program Name *', 'madhava'); ?></label>
        <input type="text" id="course_name" name="course_name" 
               value="<?php echo esc_attr($course); ?>" 
               placeholder="e.g., Web Development Internship" required />
    </div>
    
    <div class="certificate-meta-field">
        <label for="issue_date"><?php _e('Issue Date *', 'madhava'); ?></label>
        <input type="date" id="issue_date" name="issue_date" 
               value="<?php echo esc_attr($issue_date); ?>" required />
    </div>
    
    <div class="certificate-meta-field">
        <label for="duration"><?php _e('Duration', 'madhava'); ?></label>
        <input type="text" id="duration" name="duration" 
               value="<?php echo esc_attr($duration); ?>" 
               placeholder="e.g., 3 months" />
    </div>
    
    <div class="certificate-meta-field">
        <label for="description"><?php _e('Additional Description', 'madhava'); ?></label>
        <textarea id="description" name="description" 
                  placeholder="Additional information about the certificate..."><?php echo esc_textarea($description); ?></textarea>
    </div>
    
    <div class="certificate-meta-field">
        <label for="certificate_image"><?php _e('Certificate Design Image *', 'madhava'); ?></label>
        <?php
        $certificate_image = get_post_meta($post->ID, 'certificate_image', true);
        if ($certificate_image) {
            echo '<div style="margin-bottom: 10px;"><img src="' . esc_url($certificate_image) . '" style="max-width: 100%; height: auto; border: 2px solid #ddd; border-radius: 8px;" /></div>';
        }
        ?>
        <input type="hidden" id="certificate_image" name="certificate_image" value="<?php echo esc_attr($certificate_image); ?>" />
        <button type="button" class="button upload-certificate-image-btn">
            <?php echo $certificate_image ? __('Change Certificate Image', 'madhava') : __('Upload Certificate Image', 'madhava'); ?>
        </button>
        <?php if ($certificate_image) : ?>
            <button type="button" class="button remove-certificate-image-btn" style="margin-left: 10px;">Remove Image</button>
        <?php endif; ?>
        <p class="description"><?php _e('Upload your blank certificate template (the base design)', 'madhava'); ?></p>
    </div>
    
    <div class="certificate-meta-field">
        <label><?php _e('QR Code Position on Certificate', 'madhava'); ?></label>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 10px;">
            <div>
                <label for="qr_position_x" style="font-weight: normal;">X Position (pixels from left)</label>
                <input type="number" id="qr_position_x" name="qr_position_x" 
                       value="<?php echo esc_attr(get_post_meta($post->ID, 'qr_position_x', true) ?: '50'); ?>" />
            </div>
            <div>
                <label for="qr_position_y" style="font-weight: normal;">Y Position (pixels from top)</label>
                <input type="number" id="qr_position_y" name="qr_position_y" 
                       value="<?php echo esc_attr(get_post_meta($post->ID, 'qr_position_y', true) ?: '450'); ?>" />
            </div>
        </div>
        <div style="margin-top: 10px;">
            <label for="qr_size" style="font-weight: normal;">QR Code Size (pixels)</label>
            <input type="number" id="qr_size" name="qr_size" 
                   value="<?php echo esc_attr(get_post_meta($post->ID, 'qr_size', true) ?: '150'); ?>" />
        </div>
        <p class="description">
            <?php _e('Adjust position to place QR code on certificate. For bottom-left: X=50, Y=450. Experiment to find perfect spot!', 'madhava'); ?>
        </p>
    </div>
    
    <?php if ($cert_id && $post->post_status === 'publish') : 
        $qr_path = get_template_directory() . '/qr-codes/certificate-' . $cert_id . '.png';
        $qr_url = get_template_directory_uri() . '/qr-codes/certificate-' . $cert_id . '.png';
        $verification_url = home_url('/verify-certificate/?cert_id=' . urlencode($cert_id));
        $final_cert_url = get_post_meta($post->ID, 'certificate_final_image', true);
    ?>
        <div class="qr-code-section">
            <h4>📱 Certificate with QR Code</h4>
            
            <?php if ($final_cert_url && file_exists(get_template_directory() . '/certificates/certificate-' . $cert_id . '-final.jpg')) : ?>
                <div class="qr-code-container">
                    <h5>✅ Final Certificate (with QR Code Embedded)</h5>
                    <img src="<?php echo esc_url($final_cert_url . '?v=' . time()); ?>" alt="Final Certificate" />
                    <div style="margin-top: 15px;">
                        <a href="<?php echo esc_url($final_cert_url); ?>" download="certificate-<?php echo esc_attr($cert_id); ?>-final.jpg" class="download-qr-btn">
                            ⬇️ Download Final Certificate
                        </a>
                    </div>
                </div>
                
                <div class="qr-code-info">
                    <p style="margin: 0 0 8px 0;"><strong>✅ Certificate Generated Successfully!</strong></p>
                    <p style="margin: 0 0 5px 0; font-size: 12px;">The QR code has been embedded in your certificate at the specified position.</p>
                    <p style="margin: 0 0 5px 0; font-size: 12px;">Verification URL:</p>
                    <div class="qr-code-url"><?php echo esc_html($verification_url); ?></div>
                    <p style="margin: 10px 0 0 0; font-size: 11px; opacity: 0.9;">
                        💡 Tip: Download and share this certificate with students. They can scan the QR code to verify it!
                    </p>
                </div>
            <?php elseif (file_exists($qr_path)) : ?>
                <div class="qr-code-info">
                    <p style="margin: 0;">⏳ QR Code generated! Click "Update" button above to merge it with the certificate image.</p>
                </div>
            <?php else : ?>
                <div class="qr-code-info">
                    <p style="margin: 0;">⏳ Upload a certificate image above and click "Publish" to generate the final certificate with QR code.</p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <script>
    jQuery(document).ready(function($) {
        var mediaUploader;
        
        $('.upload-certificate-image-btn').on('click', function(e) {
            e.preventDefault();
            
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            
            mediaUploader = wp.media({
                title: 'Select Certificate Image',
                button: { text: 'Use this image' },
                multiple: false
            });
            
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#certificate_image').val(attachment.url);
                $('.certificate-meta-field img').remove();
                $('.upload-certificate-image-btn').before('<div style="margin-bottom: 10px;"><img src="' + attachment.url + '" style="max-width: 100%; height: auto; border: 2px solid #ddd; border-radius: 8px;" /></div>');
                $('.upload-certificate-image-btn').text('Change Certificate Image');
                
                if ($('.remove-certificate-image-btn').length === 0) {
                    $('.upload-certificate-image-btn').after('<button type="button" class="button remove-certificate-image-btn" style="margin-left: 10px;">Remove Image</button>');
                }
            });
            
            mediaUploader.open();
        });
        
        $(document).on('click', '.remove-certificate-image-btn', function(e) {
            e.preventDefault();
            $('#certificate_image').val('');
            $('.certificate-meta-field img').parent().remove();
            $('.upload-certificate-image-btn').text('Upload Certificate Image');
            $(this).remove();
        });
    });
    </script>
    <?php
}





/**
 * Generate QR Code for Certificate (Compatible with Endroid QR Code v6.x)
 */
function generate_certificate_qr_code($certificate_id) {
    // Check if Bacon QR Code is available (required by Endroid v6)
    if (!class_exists('BaconQrCode\Renderer\ImageRenderer')) {
        error_log('QR Code library not properly loaded');
        return false;
    }
    
    try {
        // Create QR code directory if it doesn't exist
        $qr_dir = get_template_directory() . '/qr-codes';
        if (!file_exists($qr_dir)) {
            wp_mkdir_p($qr_dir);
        }
        
        // URL that the QR code will redirect to
        $verification_url = home_url('/verify-certificate/?cert_id=' . urlencode($certificate_id));
        
        // Version 6.x uses Bacon QR Code under the hood
        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(300),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        
        $writer = new \BaconQrCode\Writer($renderer);
        $qr_code_svg = $writer->writeString($verification_url);
        
        // Save SVG file
        $filename = $qr_dir . '/certificate-' . $certificate_id . '.svg';
        file_put_contents($filename, $qr_code_svg);
        
        // Also create PNG version using GD (if available)
        if (extension_loaded('gd')) {
            $png_renderer = new \BaconQrCode\Renderer\ImageRenderer(
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(300),
                new \BaconQrCode\Renderer\Image\ImagickImageBackEnd()
            );
            
            $png_writer = new \BaconQrCode\Writer($png_renderer);
            $png_filename = $qr_dir . '/certificate-' . $certificate_id . '.png';
            $png_writer->writeFile($verification_url, $png_filename);
        }
        
        error_log('QR Code generated successfully: ' . $filename);
        return true;
        
    } catch (Exception $e) {
        error_log('QR Code Generation Error: ' . $e->getMessage());
        return false;
    }
}

    /**
 * Merge QR Code with Certificate Image
 */
function merge_qr_code_with_certificate($certificate_id) {
    // Check if GD library is available
    if (!extension_loaded('gd')) {
        error_log('GD library not available for image processing');
        return false;
    }
    
    try {
        // Get certificate post
        $args = array(
            'post_type' => 'certificate',
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => 'certificate_id',
                    'value' => $certificate_id,
                    'compare' => '='
                )
            )
        );
        
        $query = new WP_Query($args);
        
        if (!$query->have_posts()) {
            return false;
        }
        
        $query->the_post();
        $post_id = get_the_ID();
        
        // Get certificate image URL
        $certificate_image_url = get_post_meta($post_id, 'certificate_image', true);
        
        if (empty($certificate_image_url)) {
            wp_reset_postdata();
            return false;
        }
        
        // Get QR code position settings
        $qr_x = intval(get_post_meta($post_id, 'qr_position_x', true) ?: 50);
        $qr_y = intval(get_post_meta($post_id, 'qr_position_y', true) ?: 450);
        $qr_size = intval(get_post_meta($post_id, 'qr_size', true) ?: 150);
        
        wp_reset_postdata();
        
        // Paths
        $theme_dir = get_template_directory();
        $qr_dir = $theme_dir . '/qr-codes';
        $certificates_dir = $theme_dir . '/certificates';
        
        // Create certificates directory if it doesn't exist
        if (!file_exists($certificates_dir)) {
            wp_mkdir_p($certificates_dir);
        }
        
        $qr_path = $qr_dir . '/certificate-' . $certificate_id . '.png';
        $output_path = $certificates_dir . '/certificate-' . $certificate_id . '-final.jpg';
        
        // Check if QR code exists
        if (!file_exists($qr_path)) {
            error_log('QR code not found: ' . $qr_path);
            return false;
        }
        
        // Download certificate image if it's a URL
        $certificate_path = $certificate_image_url;
        if (strpos($certificate_image_url, 'http') === 0) {
            // Convert URL to local path
            $upload_dir = wp_upload_dir();
            $certificate_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $certificate_image_url);
        }
        
        // Load certificate image
        $cert_ext = strtolower(pathinfo($certificate_path, PATHINFO_EXTENSION));
        
        if ($cert_ext === 'jpg' || $cert_ext === 'jpeg') {
            $certificate_img = imagecreatefromjpeg($certificate_path);
        } elseif ($cert_ext === 'png') {
            $certificate_img = imagecreatefrompng($certificate_path);
        } else {
            error_log('Unsupported certificate image format: ' . $cert_ext);
            return false;
        }
        
        if (!$certificate_img) {
            error_log('Failed to create image from certificate');
            return false;
        }
        
        // Load QR code
        $qr_img = imagecreatefrompng($qr_path);
        
        if (!$qr_img) {
            error_log('Failed to load QR code image');
            imagedestroy($certificate_img);
            return false;
        }
        
        // Resize QR code if needed
        $qr_resized = imagescale($qr_img, $qr_size, $qr_size);
        
        // Add white background to QR code for better visibility
        $qr_with_bg = imagecreatetruecolor($qr_size + 20, $qr_size + 20);
        $white = imagecolorallocate($qr_with_bg, 255, 255, 255);
        imagefill($qr_with_bg, 0, 0, $white);
        
        // Copy QR code onto white background
        imagecopy($qr_with_bg, $qr_resized, 10, 10, 0, 0, $qr_size, $qr_size);
        
        // Merge QR code with certificate
        imagecopy($certificate_img, $qr_with_bg, $qr_x, $qr_y, 0, 0, $qr_size + 20, $qr_size + 20);
        
        // Save final certificate with QR code
        $result = imagejpeg($certificate_img, $output_path, 95);
        
        // Clean up
        imagedestroy($certificate_img);
        imagedestroy($qr_img);
        imagedestroy($qr_resized);
        imagedestroy($qr_with_bg);
        
        if ($result) {
            // Save the final certificate URL to post meta
            $output_url = get_template_directory_uri() . '/certificates/certificate-' . $certificate_id . '-final.jpg';
            update_post_meta($post_id, 'certificate_final_image', $output_url);
            
            error_log('Certificate with QR code created: ' . $output_path);
            return $output_url;
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log('Error merging QR code with certificate: ' . $e->getMessage());
        return false;
    }
}



/**
 * Save Certificate Meta
 */
function save_certificate_meta($post_id) {
    // Security checks
    if (!isset($_POST['certificate_meta_nonce']) || 
        !wp_verify_nonce($_POST['certificate_meta_nonce'], 'save_certificate_meta')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Save meta fields
    $fields = array(
    'certificate_id', 
    'recipient_name', 
    'course_name', 
    'issue_date', 
    'duration', 
    'description',
    'certificate_image',
    'qr_position_x',
    'qr_position_y',
    'qr_size'
);
    
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
    // Generate QR Code and merge with certificate
if (isset($_POST['certificate_id']) && !empty($_POST['certificate_id']) && 
    isset($_POST['certificate_image']) && !empty($_POST['certificate_image']) &&
    get_post_status($post_id) === 'publish') {
    
    $cert_id = sanitize_text_field($_POST['certificate_id']);
    
    // First generate QR code
    $qr_generated = generate_certificate_qr_code($cert_id);
    
    // Then merge it with certificate image
    if ($qr_generated) {
        merge_qr_code_with_certificate($cert_id);
    }
}

}
add_action('save_post_certificate', 'save_certificate_meta');


/**
 * Admin notice if QR Code library is not installed
 */
function certificate_qr_admin_notice() {
    if (!class_exists('Endroid\QrCode\QrCode')) {
        $screen = get_current_screen();
        if ($screen && $screen->post_type === 'certificate') {
            ?>
            <div class="notice notice-warning">
                <p>
                    <strong><?php _e('QR Code Library Missing:', 'madhava'); ?></strong>
                    <?php _e('Please install the QR Code library by running:', 'madhava'); ?>
                    <code>composer require endroid/qr-code</code>
                    <?php _e('in your theme directory.', 'madhava'); ?>
                </p>
            </div>
            <?php
        }
    }
}
add_action('admin_notices', 'certificate_qr_admin_notice');
// --------------------------------------------------------------------------



// Register Course Custom Post Type
function madhava_register_course_post_type() {
    $labels = array(
        'name'                  => 'Courses',
        'singular_name'         => 'Course',
        'menu_name'             => 'Courses',
        'add_new'              => 'Add New Course',
        'add_new_item'         => 'Add New Course',
        'edit_item'            => 'Edit Course',
        'new_item'             => 'New Course',
        'view_item'            => 'View Course',
        'search_items'         => 'Search Courses',
        'not_found'            => 'No courses found',
        'not_found_in_trash'   => 'No courses found in Trash',
    );

    $args = array(
        'labels'              => $labels,
        'public'              => true,
        'has_archive'         => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'query_var'           => true,
        'rewrite'             => array('slug' => 'courses'),
        'capability_type'     => 'post',
        'hierarchical'        => false,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-welcome-learn-more',
        'supports'            => array('title', 'editor', 'thumbnail', 'excerpt'),
        'show_in_rest'        => true,
    );

    register_post_type('course', array(
    'labels' => $labels,
    'public' => true,
    'has_archive' => 'courses',
    'publicly_queryable' => true,
    'show_ui' => true,
    'show_in_menu' => true,
    'query_var' => true,
    'rewrite' => array(
        'slug' => 'courses',
        'with_front' => false, // <-- IMPORTANT for custom structure!
    ),
    'capability_type' => 'post',
    'hierarchical' => false,
    'menu_position' => 5,
    'menu_icon' => 'dashicons-welcome-learn-more',
    'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
    'show_in_rest' => true,
));

}
add_action('init', 'madhava_register_course_post_type');

// Add Custom Meta Boxes for Course Information
function madhava_add_course_meta_boxes() {
    add_meta_box(
        'course_details',
        'Course Details',
        'madhava_course_details_callback',
        'course',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'madhava_add_course_meta_boxes');

function madhava_course_details_callback($post) {
    wp_nonce_field('madhava_save_course_details', 'madhava_course_nonce');

    $tutor_name = get_post_meta($post->ID, '_course_tutor_name', true);
    $duration = get_post_meta($post->ID, '_course_duration', true);
    $price = get_post_meta($post->ID, '_course_price', true);
    $course_level = get_post_meta($post->ID, '_course_level', true);
    $students_enrolled = get_post_meta($post->ID, '_students_enrolled', true);
    $course_contents = get_post_meta($post->ID, '_course_contents', true);
    $what_you_learn = get_post_meta($post->ID, '_what_you_learn', true);
    $prerequisites = get_post_meta($post->ID, '_prerequisites', true);

    // NEW: course type meta
    $course_type = get_post_meta($post->ID, '_course_type', true);
    ?>
    <div style="padding: 10px;">
        <p>
            <label for="course_tutor_name" style="display: inline-block; width: 150px; font-weight: bold;">Tutor Name:</label>
            <input type="text" id="course_tutor_name" name="course_tutor_name" value="<?php echo esc_attr($tutor_name); ?>" style="width: 60%;" />
        </p>

        <!-- NEW field: Course Type -->
        <p>
            <label for="course_type" style="display: inline-block; width: 150px; font-weight: bold;">Course Type:</label>
            <input type="text" id="course_type" name="course_type" value="<?php echo esc_attr($course_type); ?>" placeholder="e.g., Project-Based, Beginner to Advanced" style="width: 60%;" />
            <br><small>Enter a short label (used as a pill on the course card)</small>
        </p>

        <p>
            <label for="course_duration" style="display: inline-block; width: 150px; font-weight: bold;">Duration:</label>
            <input type="text" id="course_duration" name="course_duration" value="<?php echo esc_attr($duration); ?>" placeholder="e.g., 8 Weeks" style="width: 60%;" />
        </p>

        <p>
            <label for="course_price" style="display: inline-block; width: 150px; font-weight: bold;">Price:</label>
            <input type="text" id="course_price" name="course_price" value="<?php echo esc_attr($price); ?>" placeholder="e.g., ₹15,000" style="width: 60%;" />
        </p>

        <p>
            <label for="course_level" style="display: inline-block; width: 150px; font-weight: bold;">Course Level:</label>
            <select id="course_level" name="course_level" style="width: 60%;">
                <option value="">Select Level</option>
                <option value="Beginner" <?php selected($course_level, 'Beginner'); ?>>Beginner</option>
                <option value="Intermediate" <?php selected($course_level, 'Intermediate'); ?>>Intermediate</option>
                <option value="Advanced" <?php selected($course_level, 'Advanced'); ?>>Advanced</option>
                <option value="All Levels" <?php selected($course_level, 'All Levels'); ?>>All Levels</option>
            </select>
        </p>

        <p>
            <label for="students_enrolled" style="display: inline-block; width: 150px; font-weight: bold;">Students Enrolled:</label>
            <input type="number" id="students_enrolled" name="students_enrolled" value="<?php echo esc_attr($students_enrolled); ?>" placeholder="e.g., 150" style="width: 60%;" />
        </p>

        <p>
            <label for="what_you_learn" style="display: block; font-weight: bold; margin-bottom: 5px;">What You'll Learn:</label>
            <textarea id="what_you_learn" name="what_you_learn" rows="4" style="width: 100%;" placeholder="One item per line"><?php echo esc_textarea($what_you_learn); ?></textarea>
            <small>Enter each learning point on a new line</small>
        </p>

        <p>
            <label for="course_contents" style="display: block; font-weight: bold; margin-bottom: 5px;">
                Course Contents:
            </label>
            <textarea id="course_contents" name="course_contents" rows="6" style="width: 100%;" placeholder="Use this format: Week 1 – Java Basics & Programming Foundation - Introduction to Java: features, where used - JVM, JDK, JRE: overview and roles - Installing Java and setting up VS Code/IntelliJ  Week 2 – OOP Concepts - Classes & Objects - Methods - Inheritance, polymorphism, encapsulation"><?php echo esc_textarea( $course_contents ); ?></textarea>
            <small>Use plain lines for module headings (e.g. “Week 1 – Java Basics”) and start each topic under it with a hyphen (<code>-</code>).</small>
        </p>

        <p>
            <label for="prerequisites" style="display: block; font-weight: bold; margin-bottom: 5px;">Prerequisites:</label>
            <textarea id="prerequisites" name="prerequisites" rows="3" style="width: 100%;" placeholder="One prerequisite per line"><?php echo esc_textarea($prerequisites); ?></textarea>
            <small>Enter each prerequisite on a new line</small>
        </p>
    </div>
    <?php
}


function madhava_save_course_details($post_id) {
    if (!isset($_POST['madhava_course_nonce']) || !wp_verify_nonce($_POST['madhava_course_nonce'], 'madhava_save_course_details')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Text fields
    $text_fields = array(
        'course_tutor_name',
        'course_duration',
        'course_price',
        'course_level',
        'students_enrolled',
        'course_type' // <-- NEW: save course type
    );

    foreach ($text_fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
        }
    }

    // Textarea fields (preserve line breaks)
    $textarea_fields = array(
        'course_contents',
        'what_you_learn',
        'prerequisites'
    );

    foreach ($textarea_fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, '_' . $field, sanitize_textarea_field($_POST[$field]));
        }
    }
}
add_action('save_post', 'madhava_save_course_details');



// --------------------------------------------------------------------------



// Handle Course Enrollment Form Submission
add_action('wp_ajax_submit_course_enrollment', 'handle_course_enrollment_submission');
add_action('wp_ajax_nopriv_submit_course_enrollment', 'handle_course_enrollment_submission');

function handle_course_enrollment_submission() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'enrollment_nonce')) {
        wp_send_json_error(array('message' => 'Security verification failed'));
        return;
    }

    // Get and sanitize form data
    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $phone = sanitize_text_field($_POST['phone']);
    $address = sanitize_textarea_field($_POST['address']);
    $course = sanitize_text_field($_POST['course']);

    // Validate required fields
    if (empty($name) || empty($email) || empty($phone) || empty($course)) {
        wp_send_json_error(array('message' => 'Please fill in all required fields'));
        return;
    }

    // Prepare email to admin
    $to = 'sarita@madhavatechnology.com, info@madhavatechnology.com'; 
    $subject = 'New Course Enrollment: ' . $course;
    
    $message = "You have received a new course enrollment submission:\n\n";
    $message .= "Full Name: " . $name . "\n";
    $message .= "Email: " . $email . "\n";
    $message .= "Phone: " . $phone . "\n";
    $message .= "Address: " . $address . "\n";
    $message .= "Course: " . $course . "\n\n";
    $message .= "Submitted on: " . date('F j, Y, g:i a') . "\n";

    $headers = array('Content-Type: text/plain; charset=UTF-8');

    // Send email
    $sent = wp_mail($to, $subject, $message, $headers);

    if ($sent) {
        wp_send_json_success(array('message' => 'Enrollment submitted successfully'));
    } else {
        wp_send_json_error(array('message' => 'Failed to send email. Please try again.'));
    }
}



add_action( 'add_attachment', 'my_set_image_meta_upon_image_upload' );
function my_set_image_meta_upon_image_upload( $post_ID ) {
    if ( wp_attachment_is_image( $post_ID ) ) {
        $my_image_title = get_post( $post_ID )->post_title;
        $my_image_title = preg_replace( '%\s*-\s*%', ' ', $my_image_title );
        $my_image_title = ucwords( strtolower( $my_image_title ) );
        $my_image_meta = array(
            'ID' => $post_ID,
            'post_title' => $my_image_title,
            'post_content' => $my_image_title,
            'post_excerpt' => $my_image_title,
        );
        update_post_meta( $post_ID, '_wp_attachment_image_alt', $my_image_title );
        wp_update_post( $my_image_meta );
    }
}
// Set max upload size to 50 MB
add_filter( 'upload_size_limit', 'change_upload_size' );
function change_upload_size( $size ) {
    return 1024 * 1024 * 50;
}

// Handle Contact Form Submission
add_action('wp_ajax_submit_contact_form', 'handle_contact_form_submission');
add_action('wp_ajax_nopriv_submit_contact_form', 'handle_contact_form_submission');

function handle_contact_form_submission() {
    // Verify nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'contact_form_nonce')) {
        wp_send_json_error(['message' => 'Security verification failed']);
        return;
    }

    // Get and sanitize form data
    $name      = sanitize_text_field($_POST['name']);
    $email     = sanitize_email($_POST['email']);
    $phone     = sanitize_text_field($_POST['phone']);
    $address   = sanitize_textarea_field($_POST['address']);
    $additional = sanitize_textarea_field($_POST['additional']);
    $service   = sanitize_text_field($_POST['service']);

    // Validate required fields
    if (empty($name) || empty($email) || empty($phone)) {
        wp_send_json_error(['message' => 'Please fill in all required fields']);
        return;
    }

    // Prepare email to admin
    $to = ['sarita@madhavatechnology.com', 'info@madhavatechnology.com', 'jdixit088@gmail.com'];
    $subject = 'New Contact Form Submission: ' . $service;

    $message = "You have received a new contact form submission:\n\n";
    $message .= "Full Name: " . $name . "\n";
    $message .= "Email: " . $email . "\n";
    $message .= "Phone: " . $phone . "\n";
    $message .= "Address: " . $address . "\n";
    $message .= "Service of Interest: " . $service . "\n";
    $message .= "Additional Information: " . $additional . "\n\n";
    $message .= "Submitted on: " . date('F j, Y, g:i a') . "\n";

    $headers = array('Content-Type: text/plain; charset=UTF-8');

    // Send email
    $sent = wp_mail($to, $subject, $message, $headers);

    if ($sent) {
        wp_send_json_success(['message' => 'Contact form submitted successfully']);
    } else {
        wp_send_json_error(['message' => 'Failed to send email. Please try again.']);
    }
}
