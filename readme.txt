=== Smart CSV Importer ===
Contributors: wapon
Donate link: https://wapon.co.jp/
Tags: csv, import, export, posts, bulk edit, importer
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.0.0
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Easily import and export WordPress posts via CSV files with a modern drag-and-drop interface. Supports custom fields, categories, tags, and featured images.

== Description ==

Smart CSV Importer is a powerful WordPress plugin that allows you to easily import and export posts using CSV files. With its modern drag-and-drop interface and comprehensive field support, managing large amounts of content has never been easier.

= Key Features =

* **Modern Drag & Drop Interface** - Beautiful gradient design with smooth animations
* **Bulk Import/Export** - Import and export unlimited posts via CSV files
* **Update Existing Posts** - Modify existing posts by specifying post ID
* **Comprehensive Field Support** - Posts, pages, custom post types, categories, tags, custom fields
* **Featured Image Support** - Automatically download and set featured images from URLs
* **Hierarchical Structure** - Support for parent pages and menu ordering
* **Sample CSV Download** - Get started quickly with sample CSV templates
* **Multilingual Ready** - Fully translatable (includes Japanese and English)
* **Safe & Secure** - Proper nonce validation, data sanitization, and user permission checks

= Perfect For =

* Migrating content from other platforms
* Bulk editing existing posts
* Managing content via spreadsheets
* Receiving content from external writers in CSV format
* Creating backups of your posts
* Mass publishing scheduled content

= Supported Post Types =

* Posts
* Pages
* Custom Post Types

= 対応フィールド =

* post_id - 記事ID（更新時）
* title - 記事タイトル
* slug - 投稿スラッグ
* type - 投稿タイプ（post/page/カスタム投稿）
* parent - 親ページ
* order - 表示順序
* date - 公開日時
* status - 公開ステータス
* category - カテゴリー
* tags - タグ
* customfields-N-name - カスタムフィールド名
* customfields-N-content - カスタムフィールド値
* eyecatch - アイキャッチ画像URL
* contents - 記事本文

== Installation ==

= From WordPress Dashboard =

1. Navigate to 'Plugins > Add New' in your WordPress dashboard
2. Search for 'Smart CSV Importer'
3. Click 'Install Now'
4. Activate the plugin through the 'Plugins' menu in WordPress
5. Access the importer via 'CSV Importer' menu in the dashboard

= Manual Installation =

1. Download the plugin ZIP file
2. Upload the `smart-csv-importer` folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to 'CSV Importer' in the WordPress admin menu

= After Activation =

1. Go to 'CSV Importer' in your WordPress admin menu
2. Download the sample CSV to understand the format
3. Prepare your CSV file with the required fields
4. Drag and drop your CSV file or click to select it
5. Click 'Import' to start the process

== Frequently Asked Questions ==

= What CSV encoding should I use? =

UTF-8 is recommended. The plugin also supports UTF-8 with BOM, making it Excel-compatible.

= How many posts can I import at once? =

This depends on your server limitations, but typically the plugin can handle several thousand posts in a single import.

= Can I update existing posts? =

Yes! Simply include the post ID in the `post_id` column of your CSV file, and the plugin will update the existing post instead of creating a new one.

= What fields are supported? =

The plugin supports: post_id, title, slug, type, parent, order, date, status, category, tags, custom fields, featured image URL, and content. Download the sample CSV for a complete example.

= Will this delete my existing posts? =

No. The plugin only adds new posts or updates existing ones (when post ID is specified). It never deletes posts.

= Can I import custom post types? =

Yes! Specify the custom post type name in the `type` column.

= How are featured images handled? =

Provide the image URL in the `eyecatch` column. The plugin will automatically download the image and add it to your media library.

= How do I set categories and tags? =

List category and tag names separated by commas. Non-existent categories will be created automatically.

= Can I use unlimited custom fields? =

Yes! Use `customfields-1-name`, `customfields-2-name`, etc. for field names and `customfields-1-content`, `customfields-2-content`, etc. for values.

== Changelog ==

= 1.0.0 =
* Initial release
* CSV import functionality with drag & drop interface
* CSV export functionality for all posts
* Sample CSV download feature
* Support for posts, pages, and custom post types
* Category and tag management
* Custom fields support
* Featured image auto-download and assignment
* Multilingual support (Japanese and English)
* Modern UI with smooth animations
* Secure with proper data sanitization and validation

== Upgrade Notice ==

= 1.0.0 =
Initial release of Smart CSV Importer.

== Screenshots ==

1. Modern drag & drop import interface
2. CSV format specification and sample download
3. Export all posts to CSV
4. Success notification after import
5. File selection with preview

== Privacy Policy ==

Smart CSV Importer does not collect, store, or transmit any user data. All CSV processing happens locally on your WordPress installation.

== Support ==

For bug reports and feature requests, please visit:
https://wapon.co.jp/products/wp-plugin/smart-csv-importer
