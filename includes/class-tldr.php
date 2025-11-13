<?php
if (!defined('ABSPATH')) {
    exit;
}

class AI_Blog_TLDR {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_filter('the_content', array($this, 'auto_add_tldr'));
    }
    
    public function enqueue_assets() {
        if (!is_singular('post')) return;
        
        wp_enqueue_style('ai-tldr-style', AI_BLOG_TLDR_URL . 'assets/css/style.css', array(), AI_BLOG_TLDR_VERSION);
        wp_enqueue_script('ai-tldr-script', AI_BLOG_TLDR_URL . 'assets/js/script.js', array(), AI_BLOG_TLDR_VERSION, true);
    }
    
    public function auto_add_tldr($content) {
        if (!is_single() || !is_main_query() || !in_the_loop()) {
            return $content;
        }
        
        $settings = get_option('ai_blog_tldr_settings');
        if (empty($settings['openai_api_key'])) {
            return $content;
        }
        
        return $this->display() . $content;
    }
    
    public function display() {
        $post_id = get_the_ID();
        if (!$post_id) {
            return '';
        }
        
        $settings = get_option('ai_blog_tldr_settings');
        $post = get_post($post_id);
        $content = $this->clean_content($post->post_content);
        $url = get_permalink($post_id);
        
        $tldr = '';
        if (!empty($settings['enable_tldr'])) {
            $tldr = $this->get_or_generate_tldr($post_id, $content, $settings['openai_api_key']);
        }
        
        ob_start();
        ?>
        <div class="ai-tldr-wrapper">
            <?php if (!empty($settings['enable_tldr']) && !empty($tldr)) : ?>
            <div class="ai-tldr-container">
                <div class="ai-tldr-box">
                    <h3>📌 In short (TL;DR)</h3>
                    <div class="ai-tldr-content">
                        <?php echo wp_kses_post($tldr); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($settings['enable_buttons'])) : ?>
            <div class="ai-buttons-section">
                <p><strong>Summarize with AI:</strong></p>
                <div class="ai-buttons-wrapper">
                    <a href="#" class="ai-btn ai-btn-chatgpt" data-url="<?php echo esc_attr($url); ?>">
                        🤖 ChatGPT
                    </a>
                    <a href="#" class="ai-btn ai-btn-perplexity" data-url="<?php echo esc_attr($url); ?>">
                        🔍 Perplexity
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function get_or_generate_tldr($post_id, $content, $api_key) {
        $settings = get_option('ai_blog_tldr_settings');
        $cache_key = 'ai_tldr_' . $post_id;
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $tldr = $this->generate_with_chatgpt($content, $api_key);
        
        if ($tldr) {
            $hours = intval($settings['cache_hours'] ?? 24);
            set_transient($cache_key, $tldr, $hours * HOUR_IN_SECONDS);
            return $tldr;
        }
        
        return '';
    }
    
    private function generate_with_chatgpt($content, $api_key) {
        $content = substr($content, 0, 4000);
        
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => "Create a TL;DR for this article:\n\n1. Write 2-3 sentences overview.\n2. List 3-4 key bullet points (start with '-').\n\nArticle:\n" . $content
                    )
                ),
                'max_tokens' => 300,
                'temperature' => 0.7,
            )),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $api_key,
                'Content-Type: application/json',
            ),
        ));
        
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);
        
        if ($err || $http_code !== 200) {
            error_log('AI Blog TLDR Error: ' . ($err ?: 'HTTP ' . $http_code));
            return null;
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            return $this->format_tldr($data['choices'][0]['message']['content']);
        }
        
        return null;
    }
    
    private function format_tldr($text) {
        $lines = explode("\n", trim($text));
        $overview = '';
        $bullets = array();
        $in_bullets = false;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            if (preg_match('/^[\-\*\d\.]\s+/', $line)) {
                $in_bullets = true;
                $clean = preg_replace('/^[\-\*\d\.]\s+/', '', $line);
                $bullets[] = $clean;
            } elseif (!$in_bullets) {
                $overview .= $line . ' ';
            }
        }
        
        $html = '';
        if (!empty($overview)) {
            $html .= '<p>' . esc_html(trim($overview)) . '</p>';
        }
        
        if (!empty($bullets)) {
            $html .= '<div class="ai-tldr-highlights"><strong>Key Points:</strong><ul>';
            foreach ($bullets as $bullet) {
                $html .= '<li>' . esc_html($bullet) . '</li>';
            }
            $html .= '</ul></div>';
        }
        
        return $html;
    }
    
    private function clean_content($content) {
        $content = strip_shortcodes($content);
        $content = wp_strip_all_tags($content);
        $content = preg_replace('/\s+/', ' ', $content);
        return trim($content);
    }
}
?>
