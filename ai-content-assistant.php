<?php
/*
Plugin Name: AI Content Assistant
Description: Generate blog posts, product descriptions and social captions from the WP admin using OpenAI. Simple prompt templates and an admin UI (Gutenberg insert supported).
Version: 1.0
Author: Sayed shagul
Text Domain: ai-content-assistant
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// --- Constants ---
define( 'AICA_VERSION', '1.0' );
define( 'AICA_OPTION_API_KEY', 'aica_openai_api_key' );



/**
 * Admin menu and settings
 */
add_action( 'admin_menu', function () {
    add_menu_page(
        'AI Content Assistant',
        'AI Content Assistant',
        'manage_options',
        'ai-content-assistant',
        'aica_admin_page',
        'dashicons-edit',
        66
    );
} );

add_action( 'admin_init', function () {
    register_setting( 'aica_settings_group', AICA_OPTION_API_KEY );
} );

/**
 * Admin page output
 */
function aica_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Insufficient privileges' );
    }
    $api_key = esc_attr( get_option( AICA_OPTION_API_KEY, '' ) );
    ?>
    
    <style>
        /* Wrapper Cards */
.aica-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    border: 1px solid #e5e7eb;
    max-width: 900px;
}

/* Headings */
.aica-heading {
    font-size: 20px;
    margin-bottom: 15px;
    color: #111827;
    font-weight: 600;
}

/* Labels */
.aica-label {
    font-size: 14px;
    font-weight: 500;
    color: #374151;
    margin-top: 10px;
    display: block;
}

/* Inputs & Selects */
.aica-input, 
.aica-select, 
.aica-textarea {
    width: 100%;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid #d1d5db;
    margin-top: 6px;
    font-size: 14px;
    line-height: 1.4;
    transition: border-color 0.3s, box-shadow 0.3s;
}

.aica-input:focus,
.aica-select:focus,
.aica-textarea:focus {
    border-color: #2563eb;
    box-shadow: 0 0 5px rgba(37, 99, 235, 0.4);
    outline: none;
}

/* Textarea */
.aica-textarea {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 1rem;
  line-height: 1.6;
  color: #222;
  background-color: #fff;
  padding: 12px;
  border-radius: 6px;
  border: 1px solid #ccc;
  letter-spacing: 0.03em;
  resize: vertical;
}

/* Buttons */
.aica-button {
    background: #f3f4f6;
    color: #111827;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.3s;
}

.aica-button.primary {
    background: #2563eb;
    color: #fff;
}

.aica-button:hover {
    background: #e5e7eb;
}

.aica-button.primary:hover {
    background: #1d4ed8;
}

/* Button Group */
.aica-button-group {
    display: flex;
    gap: 10px;
    margin-top: 12px;
}

/* Description */
.aica-description {
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
}

/* Status */
.aica-status {
    color: #6b7280;
    margin-top: 6px;
    font-size: 13px;
}
</style>

    <div class="wrap">
        <h1>AI Content Assistant</h1>
        <p>Generate blog posts, product descriptions and social captions using OpenAI. Save your API key below.</p>

<form method="post" action="options.php" class="aica-form">
    <?php settings_fields('aica_settings_group'); ?>
    <div class="aica-card">
        <h2 class="aica-heading">API Settings</h2>
        <label for="aica_api_key" class="aica-label">OpenAI API Key</label>
        <input name="<?php echo AICA_OPTION_API_KEY; ?>" id="aica_api_key" type="password" 
               value="<?php echo $api_key; ?>" class="aica-input" />
        <p class="aica-description">Enter your OpenAI API key (starts with sk-...). Stored in WP options 
        (you can also set it in wp-config.php as AICA_OPENAI_API_KEY).</p>
        <?php submit_button('Save API Key', 'primary', '', false, ['class' => 'aica-button']); ?>
    </div>
</form>

<div class="aica-card">
    <h2 class="aica-heading">Quick Generator</h2>

    <label class="aica-label">Type</label>
    <select id="aica-type" class="aica-select">
        <option value="blog">Blog Post</option>
        <option value="product">Product Description</option>
        <option value="caption">Social Caption</option>
        <option value="seo">SEO Meta + Excerpt</option>
    </select>

    <label class="aica-label">Topic / Product Title</label>
    <input id="aica-topic" type="text" placeholder="e.g. Benefits of ginger tea / ACME Widget 2.0" class="aica-input">

    <label class="aica-label">Tone & Audience</label>
    <input id="aica-tone" type="text" placeholder="e.g. friendly, professional, technical" class="aica-input">

    <label class="aica-label">Desired length</label>
    <select id="aica-length" class="aica-select">
        <option value="short">Short (150–250 words)</option>
        <option value="medium" selected>Medium (400–700 words)</option>
        <option value="long">Long (900–1200 words)</option>
    </select>

    <label class="aica-label">Extra instructions</label>
    <input id="aica-extra" type="text" placeholder="e.g. include keywords, CTA" class="aica-input">

    <div class="aica-button-group">
        <button id="aica-generate" class="aica-button primary">Generate</button>
        <button id="aica-clear" class="aica-button">Clear</button>
        <button id="aica-insert" class="aica-button" disabled>Insert</button>
    </div>

    <label class="aica-label">Generated output</label>
    <textarea id="aica-output" rows="10" class="aica-textarea"></textarea>
    <label class="aica-label">Preview</label>
<div id="aica-output-preview" class="aica-output-preview"></div>


    <div class="aica-button-group">
        <button id="aica-copy" class="aica-button" disabled>Copy</button>
        <a id="aica-download" class="aica-button" style="display:none" download="generated-content.txt">Download</a>
    </div>

    <div id="aica-status" class="aica-status"></div>
</div>

    <script>
    (function(){
        const ajaxUrl = '<?php echo admin_url( "admin-ajax.php" ); ?>';
        const nonce = '<?php echo wp_create_nonce( "aica_nonce" ); ?>';
        const btn = document.getElementById('aica-generate');
        const clearBtn = document.getElementById('aica-clear');
        const copyBtn = document.getElementById('aica-copy');
        const insertBtn = document.getElementById('aica-insert');
        const output = document.getElementById('aica-output');
        const status = document.getElementById('aica-status');
        const downloadLink = document.getElementById('aica-download');

        btn.addEventListener('click', async () => {
            const payload = {
                action: 'aica_generate',
                nonce: nonce,
                type: document.getElementById('aica-type').value,
                topic: document.getElementById('aica-topic').value,
                tone: document.getElementById('aica-tone').value,
                length: document.getElementById('aica-length').value,
                extra: document.getElementById('aica-extra').value
            };

            output.value = '';
            status.textContent = 'Generating...';
            btn.disabled = true;

            try {
                const r = await fetch(ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams(payload)
                });
                const json = await r.json();
                if (json.success) {
                    output.value = json.data.text;
                    document.getElementById('aica-output-preview').innerHTML = json.data.text
    .replace(/^### (.+)$/gm, '<strong>$1</strong>')   // H3 → bold
    .replace(/^## (.+)$/gm, '<strong style="font-size:1.1em;">$1</strong>') // H2 → bigger bold
    .replace(/^# (.+)$/gm, '<strong style="font-size:1.2em;">$1</strong>')   // H1 → largest bold
    .replace(/^\- (.+)$/gm, '<li>$1</li>')           // Convert bullet points
    .replace(/(<li>.+<\/li>)/g, '<ul>$1</ul>')       // Wrap li in ul
    .replace(/\n/g, '<br>');                         // New lines → <br>
                    status.textContent = 'Done — edit as needed.';
                    copyBtn.disabled = false;
                    insertBtn.disabled = false;
                    downloadLink.style.display = 'inline-block';
                    downloadLink.href = 'data:text/plain;charset=utf-8,' + encodeURIComponent(json.data.text);
                    downloadLink.style.display = 'inline-block';
                } else {
                    status.textContent = 'Error: ' + (json.data || json.message || 'Unknown');
                }
            } catch (err) {
                status.textContent = 'Network error. Check console.';
                console.error(err);
            } finally {
                btn.disabled = false;
            }
        });

        clearBtn.addEventListener('click', () => {
            output.value = '';
            document.getElementById('aica-output-preview').innerHTML = '';
            document.getElementById('aica-topic').value = '';
            document.getElementById('aica-extra').value = '';
            status.textContent = '';
            copyBtn.disabled = true;
            insertBtn.disabled = true;
            downloadLink.style.display = 'none';
        });

        copyBtn.addEventListener('click', () => {
            navigator.clipboard.writeText(output.value).then(() => {
                status.textContent = 'Copied to clipboard.';
            });
        });

        // Insert into Gutenberg editor if available
        insertBtn.addEventListener('click', () => {
            const text = output.value;
            if ( (typeof wp !== 'undefined') && wp.data && wp.data.dispatch ) {
                try {
                    // Attempt to insert into current editor as a paragraph block
                    wp.data.dispatch('core/editor').insertBlocks( wp.blocks.createBlock('core/paragraph', { content: text }) );
                    status.textContent = 'Inserted into editor.';
                } catch(e) {
                    // Fallback to copy
                    navigator.clipboard.writeText(text);
                    status.textContent = 'Editor insert failed; copied to clipboard.';
                }
            } else {
                navigator.clipboard.writeText(text);
                status.textContent = 'Editor not found; copied to clipboard.';
            }
        });
    })();
    
    </script>
    <?php
}

/**
 * AJAX handler: call OpenAI
 */
add_action( 'wp_ajax_aica_generate', 'aica_handle_generate' );

function aica_get_api_key() {
    // Priority: constant (in wp-config), then option
    if ( defined( 'AICA_OPENAI_API_KEY' ) && AICA_OPENAI_API_KEY ) {
        return AICA_OPENAI_API_KEY;
    }
    return get_option( AICA_OPTION_API_KEY, '' );
}

function aica_handle_generate() {
    check_ajax_referer( 'aica_nonce', 'nonce' );

    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( 'You do not have permission.' );
    }

    $type  = sanitize_text_field( $_POST['type'] ?? 'blog' );
    $topic = sanitize_text_field( $_POST['topic'] ?? '' );
    $tone  = sanitize_text_field( $_POST['tone'] ?? 'professional' );
    $length = sanitize_text_field( $_POST['length'] ?? 'medium' );
    $extra = sanitize_text_field( $_POST['extra'] ?? '' );

    if ( empty( $topic ) ) {
        wp_send_json_error( 'Please provide a topic or product title.' );
    }

    $api_key = aica_get_api_key();
    if ( empty( $api_key ) || strpos( $api_key, 'sk-' ) === false ) {
        wp_send_json_error( 'OpenAI API key not configured. Set it on the plugin settings page or define constant AICA_OPENAI_API_KEY in wp-config.php' );
    }

    // Build a prompt based on type
    $prompt = aica_build_prompt( $type, $topic, $tone, $length, $extra );

    // Call OpenAI (Chat Completions)
    $model = 'gpt-4o-mini'; // replace with your preferred model if needed
    $endpoint = 'https://api.openai.com/v1/chat/completions';

    $body = array(
        'model' => $model,
        'messages' => array(
            array( 'role' => 'system', 'content' => 'You are a helpful assistant that writes clear, SEO-friendly, professional web content.' ),
            array( 'role' => 'user', 'content' => $prompt ),
        ),
        'max_tokens' => 1200,
        'temperature' => 0.2,
        'n' => 1,
    );

    $args = array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ),
        'body' => wp_json_encode( $body ),
        'timeout' => 40,
    );

    $response = wp_remote_post( $endpoint, $args );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( 'Request failed: ' . $response->get_error_message() );
    }

    $code = wp_remote_retrieve_response_code( $response );
    $resp_body = wp_remote_retrieve_body( $response );
    $json = json_decode( $resp_body, true );

    if ( $code !== 200 || empty( $json ) ) {
        wp_send_json_error( 'OpenAI error: ' . substr( $resp_body, 0, 300 ) );
    }

    // OpenAI ChatCompletions response handling
    $text = '';
    if ( isset( $json['choices'][0]['message']['content'] ) ) {
        $text = trim( $json['choices'][0]['message']['content'] );
    } elseif ( isset( $json['choices'][0]['text'] ) ) {
        $text = trim( $json['choices'][0]['text'] );
    }

    if ( empty( $text ) ) {
        wp_send_json_error( 'Empty response from OpenAI.' );
    }

    wp_send_json_success( array( 'text' => $text ) );
}

/**
 * Build prompt templates — simple, effective prompt engineering
 */
function aica_build_prompt( $type, $topic, $tone, $length, $extra ) {

    // Map length to approximate word counts / style
    $len_map = array(
        'short' => '150-250 words',
        'medium' => '450-650 words',
        'long' => '900-1200 words'
    );
    $target = $len_map[ $length ] ?? $len_map['medium'];

    // Base instructions
    $prompt = "Write a $target $type about: \"$topic\". Tone: $tone. ";
    $prompt .= "Use clear headings, short paragraphs, and include 2-3 relevant subheadings. ";
    $prompt .= "Include an engaging intro, a short conclusion, and one call-to-action (CTA). ";

    if ( $type === 'product' ) {
        $prompt = "Write a $target SEO-friendly product description for the product titled \"$topic\". Tone: $tone. ";
        $prompt .= "Start with 2 short taglines, then a 3-paragraph description (features, benefits, use-case), and end with a 1-line CTA. Use bullet points for key features. ";
    } elseif ( $type === 'caption' ) {
        $prompt = "Write 5 short social media captions (1-2 lines each) for \"$topic\" aimed at a $tone audience. Provide 3 hashtags per caption and a short CTA. Keep captions catchy and mobile-friendly. ";
    } elseif ( $type === 'seo' ) {
        $prompt = "Provide: 1) an SEO title (60 characters max), 2) meta description (150-160 chars), and 3) a short excerpt (40-80 words) for \"$topic\". Tone: $tone. ";
    }

    if ( ! empty( $extra ) ) {
        $prompt .= "Extra instructions: " . $extra . ". ";
    }

    // Few-shot examples (optional small prompt engineering)
    $prompt .= "\n\nExamples:\n- Headline: \"How to Improve WordPress Site Speed\". Intro: \"Site speed matters...\" (short clear intro). \n\nOutput:";
    return $prompt;
}
