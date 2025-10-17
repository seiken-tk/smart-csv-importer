<?php
/**
 * Plugin Name: Simple CSV Importer
 * Plugin URI: https://wapon.co.jp/products/wp-plugin/simple-csv-importer
 * Description: CSVファイルから記事を一括インポートするプラグイン
 * Version: 1.0.0
 * Author: Seiken TAKAMATSU (wapon Inc.)
 * Author URI: https://wapon.co.jp/
 * License: GPL2
 * Text Domain: simple-csv-importer
 */

// 直接アクセスを防止
if (!defined('ABSPATH')) {
    exit;
}

// プラグインのメインクラス
class Simple_CSV_Importer {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_post_simple_csv_import', array($this, 'handle_csv_import'));
        add_action('admin_post_simple_csv_export', array($this, 'handle_csv_export'));
    }

    // 管理メニューに追加
    public function add_admin_menu() {
        add_menu_page(
            'Simple CSV Importer',
            'CSV Importer',
            'manage_options',
            'simple-csv-importer',
            array($this, 'admin_page'),
            'dashicons-upload',
            20
        );
    }

    // 管理画面のページ
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Simple CSV Importer</h1>

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

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>CSVファイルをアップロード</h2>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="simple_csv_import">
                    <?php wp_nonce_field('simple_csv_import_action', 'simple_csv_import_nonce'); ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="csv_file">CSVファイル</label>
                            </th>
                            <td>
                                <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                                <p class="description">CSVファイルを選択してください（UTF-8推奨）</p>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button('インポート開始'); ?>
                </form>

                <hr>

                <h3>CSVフォーマット</h3>
                <p>以下の列をCSVファイルに含めてください：</p>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li><strong>post_id</strong>: 新規の場合は空白、編集の場合は記事IDを入れる</li>
                    <li><strong>title</strong>: 記事のタイトル</li>
                    <li><strong>slug</strong>: 投稿のslug、空白の場合はタイトルがそのまま入る</li>
                    <li><strong>type</strong>: 投稿post、固定ページpage、カスタム投稿</li>
                    <li><strong>parent</strong>: ページ属性 親、空白の場合は親なし</li>
                    <li><strong>order</strong>: ページ属性 順序、空白の場合は0</li>
                    <li><strong>date</strong>: 日付（未来も可能）、空白の場合はImport日時</li>
                    <li><strong>status</strong>: 公開の場合はpublish、空白の場合は下書き</li>
                    <li><strong>category</strong>: 投稿のカテゴリー</li>
                    <li><strong>tags</strong>: 投稿のタグ（,区切り）</li>
                    <li><strong>customfields-1-name</strong>: カスタムフィールド名</li>
                    <li><strong>customfields-1-content</strong>: カスタムフィールドの内容</li>
                    <li><strong>eyecatch</strong>: アイキャッチ画像のURL</li>
                    <li><strong>contents</strong>: 記事の内容（HTML可能）</li>
                </ul>
            </div>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>記事をCSVにエクスポート</h2>
                <p>すべての記事をCSVファイルとしてダウンロードできます。</p>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <input type="hidden" name="action" value="simple_csv_export">
                    <?php wp_nonce_field('simple_csv_export_action', 'simple_csv_export_nonce'); ?>
                    <?php submit_button('CSVエクスポート'); ?>
                </form>
            </div>
        </div>
        <?php
    }

    // CSVインポート処理
    public function handle_csv_import() {
        // 権限チェック
        if (!current_user_can('manage_options')) {
            wp_die('権限がありません。');
        }

        // ノンスチェック
        if (!isset($_POST['simple_csv_import_nonce']) || !wp_verify_nonce($_POST['simple_csv_import_nonce'], 'simple_csv_import_action')) {
            wp_die('不正なリクエストです。');
        }

        // ファイルがアップロードされているかチェック
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_redirect(add_query_arg('error', 'ファイルのアップロードに失敗しました。', admin_url('admin.php?page=simple-csv-importer')));
            exit;
        }

        $file = $_FILES['csv_file']['tmp_name'];

        // CSVファイルを読み込み
        $csv_data = $this->parse_csv($file);

        if (empty($csv_data)) {
            wp_redirect(add_query_arg('error', 'CSVファイルが空か、形式が正しくありません。', admin_url('admin.php?page=simple-csv-importer')));
            exit;
        }

        // データをインポート
        $result = $this->import_posts($csv_data);

        if ($result['success']) {
            $message = sprintf('%d件の記事をインポートしました。', $result['count']);
            if ($result['updated'] > 0) {
                $message .= sprintf(' (%d件を更新)', $result['updated']);
            }
            wp_redirect(add_query_arg('success', $message, admin_url('admin.php?page=simple-csv-importer')));
        } else {
            wp_redirect(add_query_arg('error', 'インポートに失敗しました。', admin_url('admin.php?page=simple-csv-importer')));
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

            // 投稿データを準備
            $post_data = array(
                'post_title'   => $row['title'],
                'post_content' => isset($row['contents']) ? $row['contents'] : '',
                'post_status'  => !empty($row['status']) ? $row['status'] : 'draft',
                'post_type'    => !empty($row['type']) ? $row['type'] : 'post',
            );

            // slug
            if (!empty($row['slug'])) {
                $post_data['post_name'] = $row['slug'];
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
                $post_data['post_date'] = $row['date'];
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
                $this->set_featured_image($post_id, $row['eyecatch']);
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
            wp_die('権限がありません。');
        }

        // ノンスチェック
        if (!isset($_POST['simple_csv_export_nonce']) || !wp_verify_nonce($_POST['simple_csv_export_nonce'], 'simple_csv_export_action')) {
            wp_die('不正なリクエストです。');
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
new Simple_CSV_Importer();
