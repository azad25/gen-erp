<?php

namespace App\Domain\CMS\Enums;

enum SectionType: string
{
    // Content Sections
    case HERO_BANNER = 'hero_banner';
    case TEXT_BLOCK = 'text_block';
    case IMAGE_TEXT = 'image_text';
    case FULL_WIDTH_IMAGE = 'full_width_image';
    case VIDEO_EMBED = 'video_embed';
    case DIVIDER = 'divider';
    
    // ERP Data Sections
    case PRODUCT_GRID = 'product_grid';
    case PORTFOLIO_GRID = 'portfolio_grid';
    case TEAM_GRID = 'team_grid';
    case BLOG_POSTS = 'blog_posts';
    
    // Engagement Sections
    case TESTIMONIALS = 'testimonials';
    case STATS = 'stats';
    case FAQ = 'faq';
    case CTA_BANNER = 'cta_banner';
    case CONTACT_FORM = 'contact_form';
    case MAP_EMBED = 'map_embed';
    
    // Advanced Sections
    case ICON_FEATURES = 'icon_features';
    case PRICING_TABLE = 'pricing_table';
    case GALLERY = 'gallery';
    case CUSTOM_HTML = 'custom_html';
    
    // E-commerce Sections
    case PRODUCT_DETAIL = 'product_detail';
    case PRODUCT_VARIANTS = 'product_variants';
    case ADD_TO_CART_BUTTON = 'add_to_cart_button';
    case SHOPPING_CART_WIDGET = 'shopping_cart_widget';
    case SHOPPING_CART_PAGE = 'shopping_cart_page';
    case CHECKOUT_FORM = 'checkout_form';
    case ORDER_CONFIRMATION = 'order_confirmation';
    case ORDER_TRACKING = 'order_tracking';
    case PRODUCT_REVIEWS = 'product_reviews';
    case REVIEW_FORM = 'review_form';
    case RELATED_PRODUCTS = 'related_products';
    case PRODUCT_SEARCH = 'product_search';
    case PRODUCT_CATEGORIES = 'product_categories';
    case FEATURED_PRODUCTS = 'featured_products';
    case NEW_ARRIVALS = 'new_arrivals';
    case BEST_SELLERS = 'best_sellers';
    case WISHLIST = 'wishlist';
    case CUSTOMER_ACCOUNT = 'customer_account';
    case ORDER_HISTORY = 'order_history';
    case CUSTOMER_LOGIN = 'customer_login';
    case CUSTOMER_REGISTER = 'customer_register';
    case CUSTOMER_PROFILE = 'customer_profile';

    // Additional Review & Wishlist Sections
    case REVIEW_SUMMARY = 'review_summary';
    case WISHLIST_BUTTON = 'wishlist_button';
    case CUSTOMER_REVIEWS = 'customer_reviews';

    public function label(): string
    {
        return match ($this) {
            self::HERO_BANNER => 'Hero Banner',
            self::TEXT_BLOCK => 'Text Block',
            self::IMAGE_TEXT => 'Image & Text',
            self::FULL_WIDTH_IMAGE => 'Full Width Image',
            self::VIDEO_EMBED => 'Video Embed',
            self::DIVIDER => 'Divider',
            self::PRODUCT_GRID => 'Product Grid',
            self::PORTFOLIO_GRID => 'Portfolio Grid',
            self::TEAM_GRID => 'Team Grid',
            self::BLOG_POSTS => 'Blog Posts',
            self::TESTIMONIALS => 'Testimonials',
            self::STATS => 'Statistics',
            self::FAQ => 'FAQ',
            self::CTA_BANNER => 'Call to Action',
            self::CONTACT_FORM => 'Contact Form',
            self::MAP_EMBED => 'Map Embed',
            self::ICON_FEATURES => 'Icon Features',
            self::PRICING_TABLE => 'Pricing Table',
            self::GALLERY => 'Gallery',
            self::CUSTOM_HTML => 'Custom HTML',
            
            // E-commerce
            self::PRODUCT_DETAIL => 'Product Detail',
            self::PRODUCT_VARIANTS => 'Product Variants',
            self::ADD_TO_CART_BUTTON => 'Add to Cart Button',
            self::SHOPPING_CART_WIDGET => 'Shopping Cart Widget',
            self::SHOPPING_CART_PAGE => 'Shopping Cart Page',
            self::CHECKOUT_FORM => 'Checkout Form',
            self::ORDER_CONFIRMATION => 'Order Confirmation',
            self::ORDER_TRACKING => 'Order Tracking',
            self::PRODUCT_REVIEWS => 'Product Reviews',
            self::REVIEW_FORM => 'Review Form',
            self::RELATED_PRODUCTS => 'Related Products',
            self::PRODUCT_SEARCH => 'Product Search',
            self::PRODUCT_CATEGORIES => 'Product Categories',
            self::FEATURED_PRODUCTS => 'Featured Products',
            self::NEW_ARRIVALS => 'New Arrivals',
            self::BEST_SELLERS => 'Best Sellers',
            self::WISHLIST => 'Wishlist',
            self::CUSTOMER_ACCOUNT => 'Customer Account',
            self::ORDER_HISTORY => 'Order History',
            self::CUSTOMER_LOGIN => 'Customer Login',
            self::CUSTOMER_REGISTER => 'Customer Register',
            self::CUSTOMER_PROFILE => 'Customer Profile',
            
            // Additional Review & Wishlist
            self::REVIEW_SUMMARY => 'Review Summary',
            self::WISHLIST_BUTTON => 'Wishlist Button',
            self::CUSTOMER_REVIEWS => 'Customer Reviews',
        };
    }

    public function category(): string
    {
        return match ($this) {
            self::HERO_BANNER, self::TEXT_BLOCK, self::IMAGE_TEXT, 
            self::FULL_WIDTH_IMAGE, self::VIDEO_EMBED, self::DIVIDER => 'Content',
            
            self::PRODUCT_GRID, self::PORTFOLIO_GRID, 
            self::TEAM_GRID, self::BLOG_POSTS => 'ERP Data',
            
            self::TESTIMONIALS, self::STATS, self::FAQ, 
            self::CTA_BANNER, self::CONTACT_FORM, self::MAP_EMBED => 'Engagement',
            
            self::ICON_FEATURES, self::PRICING_TABLE, 
            self::GALLERY, self::CUSTOM_HTML => 'Advanced',
            
            self::PRODUCT_DETAIL, self::PRODUCT_VARIANTS, self::ADD_TO_CART_BUTTON,
            self::SHOPPING_CART_WIDGET, self::SHOPPING_CART_PAGE, self::CHECKOUT_FORM,
            self::ORDER_CONFIRMATION, self::ORDER_TRACKING, self::PRODUCT_REVIEWS,
            self::REVIEW_FORM, self::RELATED_PRODUCTS, self::PRODUCT_SEARCH,
            self::PRODUCT_CATEGORIES, self::FEATURED_PRODUCTS, self::NEW_ARRIVALS,
            self::BEST_SELLERS, self::WISHLIST, self::CUSTOMER_ACCOUNT,
            self::ORDER_HISTORY, self::CUSTOMER_LOGIN, self::CUSTOMER_REGISTER,
            self::CUSTOMER_PROFILE, self::REVIEW_SUMMARY, self::WISHLIST_BUTTON,
            self::CUSTOMER_REVIEWS => 'E-commerce',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::HERO_BANNER => 'heroicons:photo',
            self::TEXT_BLOCK => 'heroicons:document-text',
            self::IMAGE_TEXT => 'heroicons:photo',
            self::FULL_WIDTH_IMAGE => 'heroicons:photo',
            self::VIDEO_EMBED => 'heroicons:play',
            self::DIVIDER => 'heroicons:minus',
            self::PRODUCT_GRID => 'heroicons:squares-2x2',
            self::PORTFOLIO_GRID => 'heroicons:briefcase',
            self::TEAM_GRID => 'heroicons:users',
            self::BLOG_POSTS => 'heroicons:newspaper',
            self::TESTIMONIALS => 'heroicons:chat-bubble-left-right',
            self::STATS => 'heroicons:chart-bar',
            self::FAQ => 'heroicons:question-mark-circle',
            self::CTA_BANNER => 'heroicons:megaphone',
            self::CONTACT_FORM => 'heroicons:envelope',
            self::MAP_EMBED => 'heroicons:map',
            self::ICON_FEATURES => 'heroicons:star',
            self::PRICING_TABLE => 'heroicons:currency-dollar',
            self::GALLERY => 'heroicons:photo',
            self::CUSTOM_HTML => 'heroicons:code-bracket',
            
            // E-commerce
            self::PRODUCT_DETAIL => 'heroicons:cube',
            self::PRODUCT_VARIANTS => 'heroicons:squares-plus',
            self::ADD_TO_CART_BUTTON => 'heroicons:shopping-cart',
            self::SHOPPING_CART_WIDGET => 'heroicons:shopping-bag',
            self::SHOPPING_CART_PAGE => 'heroicons:shopping-cart',
            self::CHECKOUT_FORM => 'heroicons:credit-card',
            self::ORDER_CONFIRMATION => 'heroicons:check-circle',
            self::ORDER_TRACKING => 'heroicons:truck',
            self::PRODUCT_REVIEWS => 'heroicons:star',
            self::REVIEW_FORM => 'heroicons:pencil-square',
            self::RELATED_PRODUCTS => 'heroicons:squares-2x2',
            self::PRODUCT_SEARCH => 'heroicons:magnifying-glass',
            self::PRODUCT_CATEGORIES => 'heroicons:tag',
            self::FEATURED_PRODUCTS => 'heroicons:fire',
            self::NEW_ARRIVALS => 'heroicons:sparkles',
            self::BEST_SELLERS => 'heroicons:trophy',
            self::WISHLIST => 'heroicons:heart',
            self::CUSTOMER_ACCOUNT => 'heroicons:user-circle',
            self::ORDER_HISTORY => 'heroicons:clipboard-document-list',
            self::CUSTOMER_LOGIN => 'heroicons:arrow-right-on-rectangle',
            self::CUSTOMER_REGISTER => 'heroicons:user-plus',
            self::CUSTOMER_PROFILE => 'heroicons:user',
            
            // Additional Review & Wishlist
            self::REVIEW_SUMMARY => 'heroicons:chart-bar',
            self::WISHLIST_BUTTON => 'heroicons:heart',
            self::CUSTOMER_REVIEWS => 'heroicons:chat-bubble-left-ellipsis',
        };
    }

    /**
     * Get default content structure for this section type.
     */
    public function getDefaultContent(): array
    {
        return match ($this) {
            self::HERO_BANNER => [
                'title' => 'Your Headline Here',
                'subtitle' => 'Supporting text that explains your value',
                'background_image' => null,
                'overlay_opacity' => 0.4,
                'cta_text' => 'Get Started',
                'cta_link' => '/contact',
                'text_align' => 'center',
                'height' => 'large',
            ],
            
            self::TEXT_BLOCK => [
                'heading' => 'Section Heading',
                'body' => '<p>Your content here...</p>',
                'align' => 'left',
                'max_width' => 'normal',
            ],
            
            self::IMAGE_TEXT => [
                'image' => null,
                'image_position' => 'left',
                'heading' => 'Feature Heading',
                'body' => '<p>Description of this feature...</p>',
                'cta_text' => '',
                'cta_link' => '',
            ],
            
            self::STATS => [
                'heading' => 'Our Numbers',
                'items' => [
                    ['number' => '500+', 'label' => 'Happy Customers'],
                    ['number' => '10', 'label' => 'Years Experience'],
                    ['number' => '50+', 'label' => 'Team Members'],
                ],
                'background' => 'light',
            ],
            
            self::PRODUCT_GRID => [
                'heading' => 'Our Products',
                'source' => 'erp',
                'limit' => 6,
                'filter_tag' => null,
                'show_price' => true,
                'layout' => '3-col',
            ],
            
            self::PORTFOLIO_GRID => [
                'heading' => 'Our Work',
                'limit' => 6,
                'layout' => 'masonry',
            ],
            
            self::TEAM_GRID => [
                'heading' => 'Meet The Team',
                'layout' => '4-col',
                'show_role' => true,
                'show_bio' => false,
            ],
            
            self::FAQ => [
                'heading' => 'Frequently Asked Questions',
                'items' => [
                    ['question' => 'Question one?', 'answer' => 'Answer one.'],
                    ['question' => 'Question two?', 'answer' => 'Answer two.'],
                ],
            ],
            
            self::CTA_BANNER => [
                'title' => 'Ready to get started?',
                'subtitle' => 'Join thousands of businesses already using our platform.',
                'button_text' => 'Contact Us',
                'button_link' => '/contact',
                'background' => 'brand',
            ],
            
            self::CONTACT_FORM => [
                'heading' => 'Get In Touch',
                'fields' => ['name', 'email', 'phone', 'message'],
                'button_text' => 'Send Message',
                'success_message' => 'Thank you! We will be in touch soon.',
            ],
            
            self::GALLERY => [
                'heading' => 'Gallery',
                'images' => [],
                'layout' => 'grid',
                'columns' => 3,
            ],
            
            self::CUSTOM_HTML => [
                'html' => '<!-- Your custom HTML here -->',
            ],
            
            // E-commerce sections
            self::PRODUCT_DETAIL => [
                'show_images' => true,
                'show_gallery' => true,
                'show_price' => true,
                'show_stock_status' => true,
                'show_sku' => true,
                'show_description' => true,
                'show_specifications' => true,
                'show_reviews' => true,
                'show_related_products' => true,
            ],
            
            self::ADD_TO_CART_BUTTON => [
                'button_text' => 'Add to Cart',
                'show_quantity_selector' => true,
                'min_quantity' => 1,
                'max_quantity' => 99,
                'show_stock_availability' => true,
                'button_style' => 'primary',
                'success_message' => 'Product added to cart!',
            ],
            
            self::SHOPPING_CART_PAGE => [
                'show_product_images' => true,
                'show_remove_button' => true,
                'show_quantity_update' => true,
                'show_subtotal' => true,
                'show_shipping_estimate' => false,
                'show_coupon_field' => false,
                'continue_shopping_url' => '/products',
                'checkout_button_text' => 'Proceed to Checkout',
            ],
            
            self::CHECKOUT_FORM => [
                'require_account' => false,
                'allow_guest_checkout' => true,
                'fields' => [
                    'email' => ['required' => true, 'label' => 'Email Address'],
                    'first_name' => ['required' => true, 'label' => 'First Name'],
                    'last_name' => ['required' => true, 'label' => 'Last Name'],
                    'phone' => ['required' => true, 'label' => 'Phone Number'],
                    'address_line_1' => ['required' => true, 'label' => 'Address'],
                    'city' => ['required' => true, 'label' => 'City'],
                    'postal_code' => ['required' => true, 'label' => 'Postal Code'],
                ],
                'payment_methods' => [
                    'cod' => ['enabled' => true, 'label' => 'Cash on Delivery'],
                    'bank_transfer' => ['enabled' => true, 'label' => 'Bank Transfer'],
                ],
                'terms_and_conditions_url' => '/terms',
                'privacy_policy_url' => '/privacy',
            ],
            
            self::ORDER_CONFIRMATION => [
                'title' => 'Thank You for Your Order!',
                'message' => 'Your order has been received and is being processed.',
                'show_order_number' => true,
                'show_order_summary' => true,
                'show_shipping_address' => true,
                'show_payment_method' => true,
                'show_tracking_link' => true,
                'continue_shopping_button' => true,
            ],
            
            self::PRODUCT_REVIEWS => [
                'title' => 'Customer Reviews',
                'show_rating_summary' => true,
                'show_rating_distribution' => true,
                'reviews_per_page' => 10,
                'allow_sorting' => true,
                'sort_options' => ['newest', 'highest_rated', 'most_helpful'],
                'show_verified_badge' => true,
            ],
            
            self::RELATED_PRODUCTS => [
                'title' => 'Related Products',
                'limit' => 4,
                'columns' => 4,
                'show_price' => true,
                'show_add_to_cart' => true,
                'algorithm' => 'category', // category, tags, viewed_together
            ],
            
            self::FEATURED_PRODUCTS => [
                'title' => 'Featured Products',
                'limit' => 8,
                'columns' => 4,
                'show_price' => true,
                'show_add_to_cart' => true,
                'filter_featured' => true,
            ],
            
            self::PRODUCT_SEARCH => [
                'title' => 'Search Products',
                'show_filters' => true,
                'filters' => ['category', 'price_range', 'brand'],
                'show_sorting' => true,
                'sort_options' => ['name', 'price_low', 'price_high', 'newest'],
                'results_per_page' => 12,
            ],
            
            self::CUSTOMER_LOGIN => [
                'title' => 'Customer Login',
                'show_register_link' => true,
                'register_link_text' => 'Don\'t have an account? Register here',
                'show_forgot_password' => true,
                'forgot_password_text' => 'Forgot your password?',
                'login_button_text' => 'Login',
                'redirect_after_login' => '/account',
            ],
            
            self::CUSTOMER_REGISTER => [
                'title' => 'Create Account',
                'show_login_link' => true,
                'login_link_text' => 'Already have an account? Login here',
                'register_button_text' => 'Create Account',
                'require_phone' => false,
                'terms_and_conditions_url' => '/terms',
                'privacy_policy_url' => '/privacy',
                'redirect_after_register' => '/account',
            ],
            
            self::CUSTOMER_PROFILE => [
                'title' => 'My Profile',
                'editable_fields' => ['first_name', 'last_name', 'phone'],
                'show_email' => true,
                'allow_password_change' => true,
                'show_order_summary' => true,
                'show_account_stats' => true,
            ],
            
            self::CUSTOMER_ACCOUNT => [
                'title' => 'My Account',
                'show_profile_summary' => true,
                'show_recent_orders' => true,
                'recent_orders_limit' => 5,
                'show_wishlist_count' => true,
                'show_account_stats' => true,
                'navigation_items' => [
                    'profile' => 'Profile',
                    'orders' => 'Order History',
                    'wishlist' => 'Wishlist',
                    'addresses' => 'Addresses',
                ],
            ],
            
            self::ORDER_HISTORY => [
                'title' => 'Order History',
                'orders_per_page' => 10,
                'show_order_status' => true,
                'show_order_total' => true,
                'show_order_date' => true,
                'show_tracking_info' => true,
                'allow_reorder' => true,
                'show_order_details_link' => true,
            ],
            
            self::REVIEW_SUMMARY => [
                'title' => 'Customer Reviews',
                'show_average_rating' => true,
                'show_total_reviews' => true,
                'show_rating_distribution' => true,
                'show_verified_badge' => true,
                'rating_scale' => 5,
            ],
            
            self::WISHLIST_BUTTON => [
                'button_text' => 'Add to Wishlist',
                'button_style' => 'outline',
                'show_icon' => true,
                'icon' => 'heart',
                'added_text' => 'Added to Wishlist',
                'removed_text' => 'Removed from Wishlist',
                'require_login' => true,
            ],
            
            self::CUSTOMER_REVIEWS => [
                'title' => 'My Reviews',
                'show_product_info' => true,
                'show_review_date' => true,
                'show_rating' => true,
                'show_verified_badge' => true,
                'allow_edit' => false,
                'allow_delete' => false,
                'reviews_per_page' => 10,
            ],
            
            default => [],
        };
    }
}