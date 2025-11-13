<?php
if (!defined('ABSPATH')) {
    exit;
}

class AI_Blog_TLDR_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    public function add_menu() {
        add_options_page(
            'AI Blog TL;DR',
            'AI Blog TL;DR',
            'manage_options',
            'ai-blog-tldr',
            array($this, 'render_page')
        );
    }
    
    public function register_settings() {
        register_setting('ai_blog_tldr_group', 'ai_blog_tldr_settings', array(
            'sanitize_callback' => array($this, 'sanitize'),
        ));
    }
    
    public function sanitize($input) {
        return array(
            'openai_api_key' => sanitize_text_field($input['openai_api_key'] ?? ''),
            'enable_tldr' => isset($input['enable_tldr']) ? 1 : 0,
            'enable_buttons' => isset($input['enable_buttons']) ? 1 : 0,
            'cache_hours' => intval($input['cache_hours'] ?? 24),
        );
    }
    
    public function enqueue_admin_assets() {
        wp_enqueue_style('ai-tldr-admin', AI_BLOG_TLDR_URL . 'assets/css/admin.css', array(), AI_BLOG_TLDR_VERSION);
    }
    
    public function render_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $settings = get_option('ai_blog_tldr_settings');
        
        if (isset($_POST['submit'])) {
            check_admin_referer('ai_blog_tldr_nonce');
        }
        ?>
        <div class="wrap ai-tldr-admin">
            <h1>AI Blog TL;DR Generator (ChatGPT)</h1>
            
            <div class="ai-tldr-card">
                <h2>Settings</h2>
                
                <form method="post" action="options.php">
                    <?php settings_fields('ai_blog_tldr_group'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="openai_api_key">OpenAI API Key</label></th>
                            <td>
                                <input 
                                    type="password" 
                                    id="openai_api_key" 
                                    name="ai_blog_tldr_settings[openai_api_key]" 
                                    value="<?php echo esc_attr($settings['openai_api_key'] ?? ''); ?>"
                                    class="regular-text"
                                    placeholder="sk-..."
                                    required
                                />
                                <p class="description">
                                    Get from: <a href="https://platform.openai.com/api-keys" target="_blank">https://platform.openai.com/api-keys</a>
                                </p>
                                <label>
                                    <input type="checkbox" id="show_key" />
                                    Show Key
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label>Features</label></th>
                            <td>
                                <label>
                                    <input 
                                        type="checkbox" 
                                        name="ai_blog_tldr_settings[enable_tldr]"
                                        <?php checked($settings['enable_tldr'] ?? 1, 1); ?>
                                    />
                                    Enable TL;DR Generation
                                </label>
                                <br/>
                                <label>
                                    <input 
                                        type="checkbox" 
                                        name="ai_blog_tldr_settings[enable_buttons]"
                                        <?php checked($settings['enable_buttons'] ?? 1, 1); ?>
                                    />
                                    Enable ChatGPT/Perplexity Buttons
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="cache_hours">Cache Duration (hours)</label></th>
                            <td>
                                <input 
                                    type="number" 
                                    id="cache_hours" 
                                    name="ai_blog_tldr_settings[cache_hours]"
                                    value="<?php echo esc_attr($settings['cache_hours'] ?? 24); ?>"
                                    min="1"
                                    max="720"
                                />
                                <p class="description">How long to cache TL;DR (1-720 hours)</p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(); ?>
                </form>
            </div>
            
            <div class="ai-tldr-card">
                <h2>Cost Estimate</h2>
                <p>ChatGPT API: ~$0.01 per article</p>
                <p>Monitor usage: <a href="https://platform.openai.com/usage" target="_blank">https://platform.openai.com/usage</a></p>
            </div>
        </div>
        
        <script>
        document.getElementById('show_key')?.addEventListener('change', function() {
            document.getElementById('openai_api_key').type = this.checked ? 'text' : 'password';
        });
        </script>
        <?php
    }
}
?>
