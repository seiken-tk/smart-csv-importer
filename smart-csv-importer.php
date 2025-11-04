<?php
/**
 * Plugin Name:       Smart CSV Importer
 * Plugin URI:        https://wapon.co.jp/products/wp-plugin/smart-csv-importer
 * Description:       CSVファイルから記事を一括インポート・エクスポートするプラグイン
 * Version:           1.0.1
 * Requires at least: 5.0
 * Requires PHP:      7.0
 * Author:            Seiken TAKAMATSU (wapon Inc.)
 * Author URI:        https://wapon.co.jp/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       smart-csv-importer
 * Domain Path:       /languages
 */

// 直接アクセスを防止
if (!defined('ABSPATH')) {
    exit;
}

// プラグインのメインクラス
class Smart_CSV_Importer {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_post_smart_csv_import', array($this, 'handle_csv_import'));
        add_action('admin_post_smart_csv_export', array($this, 'handle_csv_export'));
        add_action('admin_post_smart_csv_sample', array($this, 'handle_csv_sample'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    // CSS/JSファイルを読み込み
    public function enqueue_admin_assets($hook) {
        // このプラグインのページでのみ読み込む
        if ($hook !== 'toplevel_page_smart-csv-importer') {
            return;
        }

        // インラインスタイル
        wp_add_inline_style('wp-admin', $this->get_custom_css());

        // JavaScript用翻訳テキストをlocalize
        wp_localize_script('jquery', 'smartCsvImporter', array(
            'importing' => __('CSVをインポート中...', 'smart-csv-importer'),
            'pleaseWait' => __('しばらくお待ちください', 'smart-csv-importer'),
            'dropFile' => __('ファイルをここにドロップ', 'smart-csv-importer'),
            'clickToSelect' => __('または クリックしてファイルを選択', 'smart-csv-importer'),
        ));

        // インラインスクリプト
        wp_add_inline_script('jquery', $this->get_custom_js());
    }

    // カスタムCSS
    private function get_custom_css() {
        return "
            .smart-csv-container {
                max-width: 1200px;
                margin: 40px auto;
                padding: 0 20px;
            }

            .smart-csv-header {
                text-align: center;
                margin-bottom: 50px;
                animation: fadeInDown 0.6s ease-out;
            }

            .smart-csv-header h1 {
                font-size: 2.5rem;
                font-weight: 700;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
                margin-bottom: 10px;
            }

            .smart-csv-header p {
                color: #6b7280;
                font-size: 1.1rem;
            }

            .smart-card {
                background: #ffffff;
                border-radius: 20px;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
                padding: 40px;
                margin-bottom: 30px;
                transition: all 0.3s ease;
                animation: fadeInUp 0.6s ease-out;
            }

            .smart-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
            }

            .smart-card h2 {
                font-size: 1.8rem;
                font-weight: 600;
                color: #1f2937;
                margin-bottom: 10px;
            }

            .smart-card p {
                color: #6b7280;
                margin-bottom: 25px;
            }

            .dropzone-wrapper {
                position: relative;
                margin-bottom: 30px;
            }

            .dropzone {
                border: 3px dashed #d1d5db;
                border-radius: 15px;
                padding: 60px 20px;
                text-align: center;
                background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
                transition: all 0.3s ease;
                cursor: pointer;
                position: relative;
                overflow: hidden;
            }

            .dropzone::before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
                transition: left 0.5s ease;
            }

            .dropzone:hover::before {
                left: 100%;
            }

            .dropzone:hover {
                border-color: #667eea;
                background: linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 100%);
                transform: scale(1.02);
            }

            .dropzone.dragover {
                border-color: #667eea;
                background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
                transform: scale(1.05);
            }

            .dropzone-icon {
                font-size: 4rem;
                color: #667eea;
                margin-bottom: 20px;
                display: inline-block;
                animation: bounce 2s infinite;
            }

            .dropzone-text {
                font-size: 1.2rem;
                color: #374151;
                font-weight: 500;
                margin-bottom: 10px;
            }

            .dropzone-subtext {
                color: #9ca3af;
                font-size: 0.95rem;
            }

            .file-input-hidden {
                display: none;
            }

            .file-selected {
                background: #f0fdf4;
                border: 2px solid #10b981;
                border-radius: 10px;
                padding: 20px;
                margin-top: 20px;
                display: none;
                animation: slideIn 0.3s ease-out;
            }

            .file-selected.show {
                display: flex;
                align-items: center;
                justify-content: space-between;
            }

            .import-result {
                margin-top: 20px;
                padding: 20px;
                background: #eef2ff;
                border-left: 4px solid #667eea;
                border-radius: 12px;
            }

            .import-result-main {
                display: flex;
                align-items: baseline;
                gap: 10px;
            }

            .import-result-number {
                font-size: 2rem;
                font-weight: 700;
                color: #4338ca;
            }

            .import-result-label {
                font-size: 1rem;
                color: #374151;
            }

            .import-result-sub {
                margin-top: 8px;
                color: #6b7280;
            }

            .file-info {
                display: flex;
                align-items: center;
                gap: 15px;
            }

            .file-icon {
                font-size: 2rem;
                color: #10b981;
            }

            .file-details {
                flex: 1;
            }

            .file-name {
                font-weight: 600;
                color: #065f46;
                margin-bottom: 5px;
            }

            .file-size {
                color: #6b7280;
                font-size: 0.9rem;
            }

            .btn-remove {
                background: #ef4444;
                color: white;
                border: none;
                padding: 8px 16px;
                border-radius: 8px;
                cursor: pointer;
                font-size: 0.9rem;
                transition: all 0.2s ease;
            }

            .btn-remove:hover {
                background: #dc2626;
                transform: scale(1.05);
            }

            .btn-primary {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white !important;
                border: none;
                padding: 15px 40px;
                border-radius: 12px;
                font-size: 1.1rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
                text-shadow: none;
                height: auto;
            }

            .btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
            }

            .btn-primary:active {
                transform: translateY(0);
            }

            .btn-secondary {
                background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
                color: white !important;
                border: none;
                padding: 15px 40px;
                border-radius: 12px;
                font-size: 1.1rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4);
                text-shadow: none;
                height: auto;
            }

            .btn-secondary:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(245, 158, 11, 0.5);
            }

            .btn-download {
                background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
                color: white !important;
                border: none;
                padding: 12px 30px;
                border-radius: 10px;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(6, 182, 212, 0.4);
                text-shadow: none;
                height: auto;
                text-decoration: none;
                display: inline-block;
                margin-top: 15px;
            }

            .btn-download:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(6, 182, 212, 0.5);
                color: white !important;
            }

            .format-section {
                background: #f9fafb;
                border-radius: 12px;
                padding: 25px;
                margin-top: 30px;
            }

            .format-section h3 {
                color: #374151;
                font-size: 1.3rem;
                margin-bottom: 15px;
            }

            .format-section ul {
                list-style: none;
                padding: 0;
            }

            .format-section ul li {
                padding: 8px 0;
                padding-left: 25px;
                position: relative;
                color: #4b5563;
            }

            .format-section ul li::before {
                content: '✓';
                position: absolute;
                left: 0;
                color: #667eea;
                font-weight: bold;
            }

            .notice {
                border-radius: 12px;
                padding: 15px 20px;
                margin: 20px 0;
                animation: slideInDown 0.4s ease-out;
            }

            .notice-success {
                background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
                border-left: 4px solid #10b981;
            }

            .notice-error {
                background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
                border-left: 4px solid #ef4444;
            }

            @keyframes fadeInDown {
                from {
                    opacity: 0;
                    transform: translateY(-20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @keyframes slideIn {
                from {
                    opacity: 0;
                    max-height: 0;
                }
                to {
                    opacity: 1;
                    max-height: 200px;
                }
            }

            @keyframes slideInDown {
                from {
                    opacity: 0;
                    transform: translateY(-10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @keyframes bounce {
                0%, 100% {
                    transform: translateY(0);
                }
                50% {
                    transform: translateY(-10px);
                }
            }

            @keyframes spin {
                0% {
                    transform: rotate(0deg);
                }
                100% {
                    transform: rotate(360deg);
                }
            }

            @keyframes pulse {
                0%, 100% {
                    opacity: 1;
                }
                50% {
                    opacity: 0.5;
                }
            }

            .loading-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                display: none;
                align-items: center;
                justify-content: center;
                z-index: 999999;
                backdrop-filter: blur(5px);
            }

            .loading-overlay.show {
                display: flex;
            }

            .loading-content {
                background: white;
                padding: 40px 60px;
                border-radius: 20px;
                text-align: center;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                animation: fadeInUp 0.3s ease-out;
            }

            .loading-spinner {
                width: 60px;
                height: 60px;
                border: 5px solid #f3f4f6;
                border-top: 5px solid #667eea;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin: 0 auto 20px;
            }

            .loading-text {
                font-size: 1.2rem;
                font-weight: 600;
                color: #1f2937;
                margin-bottom: 10px;
            }

            .loading-subtext {
                font-size: 0.95rem;
                color: #6b7280;
                animation: pulse 2s ease-in-out infinite;
            }

            @media (max-width: 768px) {
                .smart-csv-header h1 {
                    font-size: 2rem;
                }

                .smart-card {
                    padding: 25px;
                }

                .dropzone {
                    padding: 40px 15px;
                }

                .dropzone-icon {
                    font-size: 3rem;
                }

                .loading-content {
                    padding: 30px 40px;
                    margin: 0 20px;
                }

                .loading-spinner {
                    width: 50px;
                    height: 50px;
                }

                .loading-text {
                    font-size: 1.1rem;
                }
            }
        ";
    }

    // カスタムJS
    private function get_custom_js() {
        return "
            jQuery(document).ready(function($) {
                var dropzone = $('#csv-dropzone');
                var fileInput = $('#csv_file');
                var fileSelected = $('#file-selected');
                var fileName = $('#file-name');
                var fileSize = $('#file-size');
                var removeBtn = $('#remove-file');
                var submitBtn = $('#submit-import');

                // ドラッグオーバー
                dropzone.on('dragover', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $(this).addClass('dragover');
                });

                // ドラッグリーブ
                dropzone.on('dragleave', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $(this).removeClass('dragover');
                });

                // ドロップ
                dropzone.on('drop', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $(this).removeClass('dragover');

                    var files = e.originalEvent.dataTransfer.files;
                    if (files.length > 0) {
                        fileInput[0].files = files;
                        showFileInfo(files[0]);
                    }
                });

                // クリックでファイル選択
                dropzone.on('click', function() {
                    fileInput.click();
                });

                // ファイル選択
                fileInput.on('change', function() {
                    if (this.files.length > 0) {
                        showFileInfo(this.files[0]);
                    }
                });

                // ファイル削除
                removeBtn.on('click', function(e) {
                    e.preventDefault();
                    fileInput.val('');
                    fileSelected.removeClass('show');
                    submitBtn.prop('disabled', true);
                });

                // ファイル情報表示
                function showFileInfo(file) {
                    fileName.text(file.name);
                    fileSize.text(formatFileSize(file.size));
                    fileSelected.addClass('show');
                    submitBtn.prop('disabled', false);
                }

                // ファイルサイズフォーマット
                function formatFileSize(bytes) {
                    if (bytes === 0) return '0 Bytes';
                    var k = 1024;
                    var sizes = ['Bytes', 'KB', 'MB', 'GB'];
                    var i = Math.floor(Math.log(bytes) / Math.log(k));
                    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
                }

                // 初期状態でボタン無効化
                submitBtn.prop('disabled', true);

                // フォーム送信時にローディング表示
                $('#csv-import-form').on('submit', function(e) {
                    // ファイルが選択されているか確認
                    if (fileInput[0].files.length === 0) {
                        return false;
                    }

                    // ローディングオーバーレイを表示
                    $('#loading-overlay').addClass('show');

                    // ボタンを無効化
                    submitBtn.prop('disabled', true);

                    // フォーム送信を続行
                    return true;
                });
            });
        ";
    }

    // 管理メニューに追加
    public function add_admin_menu() {
        add_menu_page(
            __('Smart CSV Importer', 'smart-csv-importer'),
            __('CSV Importer', 'smart-csv-importer'),
            'manage_options',
            'smart-csv-importer',
            array($this, 'admin_page'),
            'dashicons-upload',
            20
        );
    }

    // 管理画面のページ
    public function admin_page() {
        $imported_count = isset($_GET['imported']) ? absint($_GET['imported']) : null;
        $updated_count = isset($_GET['updated']) ? absint($_GET['updated']) : null;
        ?>
        <div class="wrap smart-csv-container">
            <div class="smart-csv-header">
                <h1><?php echo esc_html__('Smart CSV Importer', 'smart-csv-importer'); ?></h1>
                <p><?php echo esc_html__('CSVファイルで記事を簡単にインポート・エクスポート', 'smart-csv-importer'); ?></p>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html($_GET['success']); ?></p>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php echo esc_html($_GET['error']); ?></p>
                </div>
            <?php endif; ?>

            <div class="smart-card">
                <h2><?php echo esc_html__('📥 CSVファイルをインポート', 'smart-csv-importer'); ?></h2>
                <p><?php echo esc_html__('CSVファイルをドラッグ&ドロップするか、クリックして選択してください', 'smart-csv-importer'); ?></p>

                <?php if ($imported_count !== null): ?>
                    <div class="import-result">
                        <div class="import-result-main">
                            <span class="import-result-number"><?php echo esc_html(number_format_i18n($imported_count)); ?></span>
                            <span class="import-result-label"><?php echo esc_html__('件をインポートしました', 'smart-csv-importer'); ?></span>
                        </div>
                        <?php if ($updated_count !== null && $updated_count > 0): ?>
                            <div class="import-result-sub">
                                <?php
                                printf(
                                    esc_html__('うち%s件を更新しました', 'smart-csv-importer'),
                                    esc_html(number_format_i18n($updated_count))
                                );
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data" id="csv-import-form">
                    <input type="hidden" name="action" value="smart_csv_import">
                    <?php wp_nonce_field('smart_csv_import_action', 'smart_csv_import_nonce'); ?>

                    <div class="dropzone-wrapper">
                        <div class="dropzone" id="csv-dropzone">
                            <div class="dropzone-icon">📁</div>
                            <div class="dropzone-text"><?php echo esc_html__('ファイルをここにドロップ', 'smart-csv-importer'); ?></div>
                            <div class="dropzone-subtext"><?php echo esc_html__('または クリックしてファイルを選択', 'smart-csv-importer'); ?></div>
                        </div>
                        <input type="file" name="csv_file" id="csv_file" class="file-input-hidden" accept=".csv" required>

                        <div class="file-selected" id="file-selected">
                            <div class="file-info">
                                <div class="file-icon">✓</div>
                                <div class="file-details">
                                    <div class="file-name" id="file-name">filename.csv</div>
                                    <div class="file-size" id="file-size">0 KB</div>
                                </div>
                            </div>
                            <button type="button" class="btn-remove" id="remove-file"><?php echo esc_html__('削除', 'smart-csv-importer'); ?></button>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary" id="submit-import"><?php echo esc_html__('インポート開始', 'smart-csv-importer'); ?></button>
                </form>

                <div class="format-section">
                    <h3><?php echo esc_html__('📋 CSVフォーマット', 'smart-csv-importer'); ?></h3>
                    <p><?php echo esc_html__('以下の列をCSVファイルに含めてください：', 'smart-csv-importer'); ?></p>
                    <ul>
                        <li><strong>post_id</strong>: <?php echo esc_html__('新規の場合は空白、編集の場合は記事IDを入れる', 'smart-csv-importer'); ?></li>
                        <li><strong>title</strong>: <?php echo esc_html__('記事のタイトル', 'smart-csv-importer'); ?></li>
                        <li><strong>slug</strong>: <?php echo esc_html__('投稿のslug、空白の場合はタイトルがそのまま入る', 'smart-csv-importer'); ?></li>
                        <li><strong>type</strong>: <?php echo esc_html__('投稿post、固定ページpage、カスタム投稿', 'smart-csv-importer'); ?></li>
                        <li><strong>parent</strong>: <?php echo esc_html__('ページ属性 親、空白の場合は親なし', 'smart-csv-importer'); ?></li>
                        <li><strong>order</strong>: <?php echo esc_html__('ページ属性 順序、空白の場合は0', 'smart-csv-importer'); ?></li>
                        <li><strong>date</strong>: <?php echo esc_html__('日付（未来も可能）、空白の場合はImport日時', 'smart-csv-importer'); ?></li>
                        <li><strong>status</strong>: <?php echo esc_html__('公開の場合はpublish、空白の場合は下書き', 'smart-csv-importer'); ?></li>
                        <li><strong>category</strong>: <?php echo esc_html__('投稿のカテゴリー', 'smart-csv-importer'); ?></li>
                        <li><strong>tags</strong>: <?php echo esc_html__('投稿のタグ（,区切り）', 'smart-csv-importer'); ?></li>
                        <li><strong>customfields-1-name</strong>: <?php echo esc_html__('カスタムフィールド名', 'smart-csv-importer'); ?></li>
                        <li><strong>customfields-1-content</strong>: <?php echo esc_html__('カスタムフィールドの内容', 'smart-csv-importer'); ?></li>
                        <li><strong>eyecatch</strong>: <?php echo esc_html__('アイキャッチ画像のURL', 'smart-csv-importer'); ?></li>
                        <li><strong>contents</strong>: <?php echo esc_html__('記事の内容（HTML可能）', 'smart-csv-importer'); ?></li>
                    </ul>
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin: 0;">
                        <input type="hidden" name="action" value="smart_csv_sample">
                        <?php wp_nonce_field('smart_csv_sample_action', 'smart_csv_sample_nonce'); ?>
                        <button type="submit" class="btn-download"><?php echo esc_html__('📥 サンプルCSVをダウンロード', 'smart-csv-importer'); ?></button>
                    </form>
                </div>
            </div>

            <div class="smart-card">
                <h2><?php echo esc_html__('📤 記事をCSVにエクスポート', 'smart-csv-importer'); ?></h2>
                <p><?php echo esc_html__('すべての記事をCSVファイルとしてダウンロードできます', 'smart-csv-importer'); ?></p>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <input type="hidden" name="action" value="smart_csv_export">
                    <?php wp_nonce_field('smart_csv_export_action', 'smart_csv_export_nonce'); ?>
                    <button type="submit" class="btn-secondary"><?php echo esc_html__('CSVエクスポート', 'smart-csv-importer'); ?></button>
                </form>
            </div>
        </div>

        <!-- ローディングオーバーレイ -->
        <div class="loading-overlay" id="loading-overlay">
            <div class="loading-content">
                <div class="loading-spinner"></div>
                <div class="loading-text"><?php echo esc_html__('CSVをインポート中...', 'smart-csv-importer'); ?></div>
                <div class="loading-subtext"><?php echo esc_html__('しばらくお待ちください', 'smart-csv-importer'); ?></div>
            </div>
        </div>
        <?php
    }

    // CSVインポート処理
    public function handle_csv_import() {
        // 権限チェック
        if (!current_user_can('manage_options')) {
            wp_die(__('権限がありません。', 'smart-csv-importer'));
        }

        // ノンスチェック
        if (!isset($_POST['smart_csv_import_nonce']) || !wp_verify_nonce($_POST['smart_csv_import_nonce'], 'smart_csv_import_action')) {
            wp_die(__('不正なリクエストです。', 'smart-csv-importer'));
        }

        // ファイルがアップロードされているかチェック
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_redirect(wp_nonce_url(add_query_arg('error', urlencode(__('ファイルのアップロードに失敗しました。', 'smart-csv-importer')), admin_url('admin.php?page=smart-csv-importer'))));
            exit;
        }

        // ファイルタイプの検証
        $file_name = sanitize_file_name($_FILES['csv_file']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if ($file_ext !== 'csv') {
            wp_redirect(wp_nonce_url(add_query_arg('error', urlencode(__('CSVファイルのみアップロード可能です。', 'smart-csv-importer')), admin_url('admin.php?page=smart-csv-importer'))));
            exit;
        }

        $file = $_FILES['csv_file']['tmp_name'];

        // CSVファイルを読み込み
        $csv_data = $this->parse_csv($file);

        if (empty($csv_data)) {
            wp_redirect(wp_nonce_url(add_query_arg('error', urlencode(__('CSVファイルが空か、形式が正しくありません。', 'smart-csv-importer')), admin_url('admin.php?page=smart-csv-importer'))));
            exit;
        }

        // データをインポート
        $result = $this->import_posts($csv_data);

        if ($result['success']) {
            $message = sprintf(__('%d件の記事をインポートしました。', 'smart-csv-importer'), $result['count']);
            if ($result['updated'] > 0) {
                $message .= sprintf(__(' (%d件を更新)', 'smart-csv-importer'), $result['updated']);
            }
            $redirect_args = array(
                'success'  => urlencode($message),
                'imported' => max(0, (int) $result['count']),
            );
            if ($result['updated'] > 0) {
                $redirect_args['updated'] = max(0, (int) $result['updated']);
            }
            $redirect_url = add_query_arg($redirect_args, admin_url('admin.php?page=smart-csv-importer'));
            wp_redirect(wp_nonce_url($redirect_url));
        } else {
            wp_redirect(wp_nonce_url(add_query_arg('error', urlencode(__('インポートに失敗しました。', 'smart-csv-importer')), admin_url('admin.php?page=smart-csv-importer'))));
        }
        exit;
    }

    // CSVファイルをパース
    private function parse_csv($file) {
        $csv_data = array();

        // BOM付きUTF-8対応
        $content = file_get_contents($file);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        // 一時ファイルに書き込み
        $temp_file = tmpfile();
        fwrite($temp_file, $content);
        rewind($temp_file);

        $headers = array();
        $row_index = 0;

        while (($row = fgetcsv($temp_file, 0, ',')) !== false) {
            if ($row_index === 0) {
                // ヘッダー行
                $headers = $row;
            } else {
                // データ行
                $data = array();
                foreach ($headers as $index => $header) {
                    $data[trim($header)] = isset($row[$index]) ? $row[$index] : '';
                }
                $csv_data[] = $data;
            }
            $row_index++;
        }

        fclose($temp_file);

        return $csv_data;
    }

    // 記事をインポート
    private function import_posts($csv_data) {
        $count = 0;
        $updated = 0;

        foreach ($csv_data as $row) {
            // 空の行はスキップ
            if (empty($row['title'])) {
                continue;
            }

            // 投稿データを準備（サニタイズ）
            $post_data = array(
                'post_title'   => sanitize_text_field($row['title']),
                'post_content' => wp_kses_post(isset($row['contents']) ? $row['contents'] : ''),
                'post_status'  => !empty($row['status']) ? sanitize_key($row['status']) : 'draft',
                'post_type'    => !empty($row['type']) ? sanitize_key($row['type']) : 'post',
            );

            // slug
            if (!empty($row['slug'])) {
                $post_data['post_name'] = sanitize_title($row['slug']);
            }

            // 親ページ
            if (!empty($row['parent'])) {
                // 数値（ID）かSlugかを判定
                if (is_numeric($row['parent'])) {
                    // 数値の場合はそのままIDとして使用
                    $post_data['post_parent'] = intval($row['parent']);
                } else {
                    // Slugの場合は、そのSlugを持つ投稿を検索
                    $parent_post = get_page_by_path($row['parent'], OBJECT, 'any');
                    if ($parent_post) {
                        $post_data['post_parent'] = $parent_post->ID;
                    }
                }
            }

            // 順序
            if (!empty($row['order'])) {
                $post_data['menu_order'] = intval($row['order']);
            }

            // 日付
            if (!empty($row['date'])) {
                $post_data['post_date'] = sanitize_text_field($row['date']);
            }

            // 既存の記事を更新するか、新規作成するか
            if (!empty($row['post_id'])) {
                $post_data['ID'] = intval($row['post_id']);
                $post_id = wp_update_post($post_data);
                if ($post_id) {
                    $updated++;
                }
            } else {
                $post_id = wp_insert_post($post_data);
            }

            if (is_wp_error($post_id)) {
                continue;
            }

            // カテゴリー
            if (!empty($row['category'])) {
                $categories = array_map('trim', explode(',', $row['category']));
                $category_ids = array();
                foreach ($categories as $cat_name) {
                    $cat = get_category_by_slug(sanitize_title($cat_name));
                    if (!$cat) {
                        $cat_id = wp_create_category($cat_name);
                    } else {
                        $cat_id = $cat->term_id;
                    }
                    if ($cat_id) {
                        $category_ids[] = $cat_id;
                    }
                }
                wp_set_post_categories($post_id, $category_ids);
            }

            // タグ
            if (!empty($row['tags'])) {
                $tags = array_map('trim', explode(',', $row['tags']));
                wp_set_post_tags($post_id, $tags);
            }

            // カスタムフィールド
            foreach ($row as $key => $value) {
                if (preg_match('/^customfields-(\d+)-name$/', $key, $matches)) {
                    $index = $matches[1];
                    $field_name = $value;
                    $field_content_key = 'customfields-' . $index . '-content';

                    if (!empty($field_name) && isset($row[$field_content_key])) {
                        update_post_meta($post_id, $field_name, $row[$field_content_key]);
                    }
                }
            }

            // アイキャッチ画像
            if (!empty($row['eyecatch'])) {
                $this->set_featured_image($post_id, esc_url_raw($row['eyecatch']));
            }

            $count++;
        }

        return array(
            'success' => true,
            'count' => $count,
            'updated' => $updated
        );
    }

    // URLからアイキャッチ画像を設定
    private function set_featured_image($post_id, $image_url) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // 画像をダウンロードしてメディアライブラリに追加
        $attachment_id = media_sideload_image($image_url, $post_id, null, 'id');

        if (is_wp_error($attachment_id)) {
            return false;
        }

        // アイキャッチ画像として設定
        set_post_thumbnail($post_id, $attachment_id);

        return true;
    }

    // CSVエクスポート処理
    public function handle_csv_export() {
        // 権限チェック
        if (!current_user_can('manage_options')) {
            wp_die(__('権限がありません。', 'smart-csv-importer'));
        }

        // ノンスチェック
        if (!isset($_POST['smart_csv_export_nonce']) || !wp_verify_nonce($_POST['smart_csv_export_nonce'], 'smart_csv_export_action')) {
            wp_die(__('不正なリクエストです。', 'smart-csv-importer'));
        }

        // すべての記事を取得
        $posts = $this->get_all_posts();

        // CSVを生成
        $csv_content = $this->generate_csv($posts);

        // CSVファイルとしてダウンロード
        $filename = 'posts-export-' . date('Y-m-d-H-i-s') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // BOM付きUTF-8で出力（Excel対応）
        echo "\xEF\xBB\xBF";
        echo $csv_content;

        exit;
    }

    // サンプルCSVダウンロード処理
    public function handle_csv_sample() {
        // 権限チェック
        if (!current_user_can('manage_options')) {
            wp_die(__('権限がありません。', 'smart-csv-importer'));
        }

        // ノンスチェック
        if (!isset($_POST['smart_csv_sample_nonce']) || !wp_verify_nonce($_POST['smart_csv_sample_nonce'], 'smart_csv_sample_action')) {
            wp_die(__('不正なリクエストです。', 'smart-csv-importer'));
        }

        // サンプルCSVを生成
        $output = fopen('php://temp', 'r+');

        // ヘッダー行
        $headers = array(
            'post_id',
            'title',
            'slug',
            'type',
            'parent',
            'order',
            'date',
            'status',
            'category',
            'tags',
            'customfields-1-name',
            'customfields-1-content',
            'eyecatch',
            'contents'
        );
        fputcsv($output, $headers);

        // サンプルデータ行1
        $sample_row1 = array(
            '',  // post_id: 空白（新規投稿）
            'サンプル記事のタイトル',  // title
            'sample-post',  // slug
            'post',  // type
            '',  // parent: 空白
            '0',  // order
            '2025-01-01 10:00:00',  // date
            'publish',  // status
            'お知らせ,ニュース',  // category
            'サンプル,テスト,WordPress',  // tags
            'custom_field_1',  // customfields-1-name
            'カスタムフィールドの値',  // customfields-1-content
            'https://example.com/image.jpg',  // eyecatch
            '<p>これはサンプル記事の本文です。</p><p>HTMLタグを使用できます。</p>'  // contents
        );
        fputcsv($output, $sample_row1);

        // サンプルデータ行2
        $sample_row2 = array(
            '',  // post_id: 空白（新規投稿）
            'サンプルページ',  // title
            'sample-page',  // slug
            'page',  // type
            '',  // parent: 空白
            '0',  // order
            '',  // date: 空白（現在時刻）
            'draft',  // status
            '',  // category: ページにはカテゴリーなし
            '',  // tags: ページにはタグなし
            'page_description',  // customfields-1-name
            'ページの説明文',  // customfields-1-content
            '',  // eyecatch: 空白
            '<p>これはサンプルページの本文です。</p>'  // contents
        );
        fputcsv($output, $sample_row2);

        rewind($output);
        $csv_content = stream_get_contents($output);
        fclose($output);

        // CSVファイルとしてダウンロード
        $filename = 'sample-csv-importer.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // BOM付きUTF-8で出力（Excel対応）
        echo "\xEF\xBB\xBF";
        echo $csv_content;

        exit;
    }

    // すべての記事を取得
    private function get_all_posts() {
        $args = array(
            'post_type'      => 'any',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'orderby'        => 'ID',
            'order'          => 'ASC',
        );

        return get_posts($args);
    }

    // CSVを生成
    private function generate_csv($posts) {
        $output = fopen('php://temp', 'r+');

        // ヘッダー行
        $headers = array(
            'post_id',
            'title',
            'slug',
            'type',
            'parent',
            'order',
            'date',
            'status',
            'category',
            'tags',
            'customfields-1-name',
            'customfields-1-content',
            'eyecatch',
            'contents'
        );
        fputcsv($output, $headers);

        // データ行
        foreach ($posts as $post) {
            $row = array();

            // 基本情報
            $row[] = $post->ID;
            $row[] = $post->post_title;
            $row[] = $post->post_name;
            $row[] = $post->post_type;
            $row[] = $post->post_parent;
            $row[] = $post->menu_order;
            $row[] = $post->post_date;
            $row[] = $post->post_status;

            // カテゴリー
            $categories = wp_get_post_categories($post->ID, array('fields' => 'names'));
            $row[] = implode(',', $categories);

            // タグ
            $tags = wp_get_post_tags($post->ID, array('fields' => 'names'));
            $row[] = implode(',', $tags);

            // カスタムフィールド（最初の1つのみ）
            $custom_fields = get_post_meta($post->ID);
            $custom_field_name = '';
            $custom_field_content = '';

            if (!empty($custom_fields)) {
                foreach ($custom_fields as $key => $values) {
                    // WordPress内部のメタフィールドをスキップ
                    if (substr($key, 0, 1) !== '_') {
                        $custom_field_name = $key;
                        $custom_field_content = is_array($values) && isset($values[0]) ? $values[0] : '';
                        break;
                    }
                }
            }

            $row[] = $custom_field_name;
            $row[] = $custom_field_content;

            // アイキャッチ画像
            $thumbnail_id = get_post_thumbnail_id($post->ID);
            $eyecatch_url = '';
            if ($thumbnail_id) {
                $eyecatch_url = wp_get_attachment_url($thumbnail_id);
            }
            $row[] = $eyecatch_url;

            // 記事内容
            $row[] = $post->post_content;

            fputcsv($output, $row);
        }

        rewind($output);
        $csv_content = stream_get_contents($output);
        fclose($output);

        return $csv_content;
    }
}

// プラグインを初期化
new Smart_CSV_Importer();
