=== EDD Sale Price ===
Contributors: sormano
Tags: edd, easy digital downloads, edd sale, easy digital downloads sale, edd sale price, easy digital downloads sale price, edd promotion, easy digital downloads promotion, edd promo, easy digital downloads promo, edd discount, easy digital downloads discount
Requires at least: 4.0
Tested up to: 5.8
Stable tag: 1.0.5.1
Requires PHP: 5.6
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Promote your downloads with a sale price!

== Description ==
**Put your downloads in the spotlight by giving them a sale price!**

Numerous studies have shown that people are more likely to buy a product, no matter what, if it is has a discount. Specially if it is something they already wanted to have. The discount will trigger the potential customer to buy your products fast.

Setting up a discount correctly is important, of course you can just lower the price, but then most people will not know the product is discounted. With EDD Sale Price the regular price will be shown with a strikethrough so people will see how much it regularly costs, and what the new sale price is!

Works with both simple downloads and variable priced downloads.

**Look at the screenshots!**

**Feature requests, ratings and donations are welcome and appreciated!**

== Installation ==

1. Upload the folder `edd-sale-price to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the settings page to fine-tune the settings if desired

== Frequently Asked Questions ==

= Is the regular price displayed everywhere? =

We try to display the regular price - with a strikethrough - besides the sale price on as many places as possible.

However, due to some restrictions within Easy Digital Downloads, it will currently not display everywhere. We are working on improving this with. (it is displaying on the most important places)

= Why am I not seeing the regular price at the checkout? =

The checkout is one of those places where we improved this. But due to a new change, it does require Easy Digital Downloads 2.3+

= Can I display the regular price within the purchase button? =

It is possible through a code snippet, though there are two things to consider:
1) The purchase button will become a lot bigger
2) Due to the way the buttons are loaded, you will see (for example) \<s\>$50<\/s\> for about half a second. The 's' tags are there for the strikethrough. Its not ideal, but unfortunately we don't have any control over this.

If you'd like to activate this: `add_filter( 'eddsp_display_regular_price_text_buy_button', '__return_true' );`

== Screenshots ==

1. Overview download listing
2. Download detail page pricing
3. Checkout price - requires EDD 2.3!
4. Admin download sale price setting
5. Admin download variable sale price settings
6. Another download detail page example

== Changelog ==

= 1.0.5.1 - 23/08/2021 =

* [Fix] - 1.0.5 in some situations causing products to be 0 unintended

= 1.0.5 - 16/08/2021 =

* [Fix] - '0' not allowed as sale price

= 1.0.4 - 18/09/2017 = !! Required EDD 2.8+ !!

* [Improvement] - Use EDD 2.8 method of adding variable prices
* [Improvement] - Make use of new price filter instead of overwriting (Props to Phil Johnston)

= 1.0.3 - 28/06/2016 =

* [Fix] - Notice caused on checkout pre-save of sale price fields
* Bump tested up to version to WP 4.8

= 1.0.2 - 18/07/2015 =

* [i18n] - Add French translation

= 1.0.1 - 25/05/2015 =

* [Fix] - Sale price incorrect in admin dashboard
* [Fix] - Incorrect sale price in cart when adding a variable download
* [Improvement] - Add filter to allow the regular price with a strikethrough on the buy button.

= 1.0.0 - 22/02/2015 =

* Initial release
