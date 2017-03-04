# WPAdverts bbPress Integration
Quick WPAdverts bbPress Integration

This simple addon will let you add the following to your bbPress forums:

- Register a custom profile tab at `/portfolio` to show the user's adverts
- Add a link to the adverts page for each user under avatar and author details in the forums
- Display 1 random advert below each topic, for the topic author.

All you need to do is activate the plugin and it will do the job. it does not come with a GUI for settings in case you were confused, but extending its features is quite easy.

** Change advert limit below topic from 1 to more **

```php
add_filter('wabbp_parse_topic_adverts_parse_query', function($args){
	$args['posts_per_page'] = 5; // changed from 1 to 5

	return $args;
});```

** Disable the bbPress profile tab **

```php
add_filter('wpadvbbp', function($args){
	unset($args['profile-tab']);

	return $args;
});```

** Adjust the link to portfolio **

```php
add_filter('wabbp_forum_reply_portfolio_link', function($link, $user_id){
	// point it out to example.com/samuel/adverts/
	return home_url( get_userdata($user_id)->user_nicename . '/portfolio/' );
}, 10, 2);
```

** Change the "Portfolio (%d)" link text **

```php
add_filter('wpadvbbp_menu_item_text', function() {
	return 'My Portfolio (%d)'; // or 'My Portfolio' without count
});
```