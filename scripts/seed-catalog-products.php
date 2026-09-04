<?php
// One-time content migration, kept here for history/reference only — NOT
// part of bootstrap.sh or deploy.sh, and NOT safe to run as-is against a
// fresh environment. Ports the original mock catalog (recovered from
// limak_website git history before it was replaced with a real API
// fetch) into real products, so the same nine items kept showing up on
// the live site after the frontend swap. Already run once in production.
//
// Requires the 8 shared catalog photos
// (public/assets/products/{1..8}.jpeg in limak_website) to already be
// imported via `wp media import`, with their resulting attachment IDs
// substituted into the $products array below in place of the hardcoded
// 7-14 (which matched this project's database at the time this ran).
// Idempotent by slug — re-running with the same IDs skips existing
// products rather than duplicating them.
//
// USD mock prices carried over at a placeholder x40,000 scaling to get a
// plausible Toman figure preserving the original relative ordering — not
// a real conversion, just a starting point editable in wp-admin.
//
// Usage: docker compose -f docker-compose.prod.yml run --rm --entrypoint wp wpcli eval-file scripts/seed-catalog-products.php

$gallery_source = new \Limak\Headless\Support\Media\Postmeta_Gallery_Source();

$products = [
	[
		'slug'             => 'aurelia-sofa',
		'category'         => 'sofas',
		'featured'         => true,
		'price'            => 288000000,
		'photos'           => [ 7, 8, 9 ],
		'name'             => [ 'fa' => 'مبل آورلیا', 'en' => 'The Aurelia Sofa' ],
		'shortDescription' => [ 'fa' => 'مبلی سه‌نفره و پهن با پشتی کوتاه و نشیمن عمیق، ساخته‌شده برای فضاهایی که آدم‌ها را دور هم جمع می‌کنند.', 'en' => 'A wide three-seat sofa with a low back and deep cushions, built for rooms that gather people.' ],
		'description'      => [
			'fa' => [
				'آورلیا از یک قطعه یکپارچه گردوی کهن‌رشد بریده می‌شود؛ قابی به اندازه‌ای پهن که سه نفر را بدون ازدحام جای می‌دهد و به اندازه‌ای کوتاه که زیر خط پنجره محو می‌شود. کوسن‌های پشتی در سه تراکم پر می‌شوند — سفت در پایه، نرم در بالا — تا نشیمن پس از سال‌ها استفاده شکل خود را حفظ کند، نه آنکه به وسط فرو بریزد.',
				'هر آورلیا پس از عبور قاب از بازرسی، با پارچه یا چرمی از کتابخانه فعلی آتلیه، به‌صورت دستی رویه‌دوزی می‌شود. از سفارش تا تحویل، هشت تا ده هفته زمان می‌برد؛ همان زمان‌بندیِ باقی مجموعه.',
			],
			'en' => [
				'The Aurelia is cut from a single run of old-growth walnut, with a frame wide enough to seat three without crowding and low enough to disappear under a window line. The back cushions are filled in three densities — firm at the base, soft at the crown — so the seat holds its shape after years of use instead of collapsing into the middle.',
				"Every Aurelia is upholstered by hand after the frame passes inspection, in a fabric or leather chosen from the atelier's current library. Expect eight to ten weeks from order to delivery, the same lead time as the rest of the collection.",
			],
		],
		'dimensions'       => [ 228, 96, 74 ],
		'finishes'         => [
			[ 'name' => 'چرم امبر', 'name_en' => 'Umber Leather', 'color' => '#6b4a34' ],
			[ 'name' => 'کتان استخوانی', 'name_en' => 'Bone Linen', 'color' => '#e7e0d3' ],
			[ 'name' => 'بوکله سبز تیره', 'name_en' => 'Ink Green Bouclé', 'color' => '#3a4a3f' ],
		],
		'sizes'            => [ [ 'label' => 'دونفره', 'label_en' => 'Two-Seat' ], [ 'label' => 'سه‌نفره', 'label_en' => 'Three-Seat' ] ],
	],
	[
		'slug'             => 'harlow-sofa',
		'category'         => 'sofas',
		'featured'         => false,
		'price'            => 216000000,
		'photos'           => [ 10, 11 ],
		'name'             => [ 'fa' => 'مبل هارلو', 'en' => 'The Harlow Sofa' ],
		'shortDescription' => [ 'fa' => 'مبلی دونیم‌نفره و جمع‌وجورتر برای فضاهای کوچک‌تر، بدون از دست دادن عمقِ قطعات بزرگ‌تر مجموعه.', 'en' => "A tighter two-and-a-half seat sofa for smaller rooms, without losing the depth of the larger pieces." ],
		'description'      => [
			'fa' => [
				'هارلو تناسبات آورلیا را برای آپارتمان‌ها و اتاق‌های کوچک‌تر کوچک می‌کند؛ همان پشتی کوتاه و نشیمن عمیق را حفظ می‌کند اما قاب را به کمی کمتر از دو متر می‌رساند. این قطعه‌ای است که مشتریان بیشتر از همه برای خانه دوم انتخاب می‌کنند.',
				'پایه با چوب بلوط تیره تمام می‌شود که از هر چهار طرف زیر رویه‌دوزی دیده می‌شود، تا مبل بیشتر شبیه یک اثاثیه به‌نظر برسد تا یک حجم ثابت در اتاق.',
			],
			'en' => [
				"Harlow scales the Aurelia's proportions down for apartments and studies, keeping the same low back and deep seat but trimming the frame to just under two meters. It's the piece clients choose most often for a second home.",
				"The base is finished in a dark oak leg, visible beneath the upholstery on all four sides, so the sofa reads as furniture rather than a fixed block in the room.",
			],
		],
		'dimensions'       => [ 196, 92, 74 ],
	],
	[
		'slug'             => 'foxglove-armchair',
		'category'         => 'armchairs',
		'featured'         => false,
		'price'            => 128000000,
		'photos'           => [ 12, 13 ],
		'name'             => [ 'fa' => 'صندلی راحتی فاکسگلاو', 'en' => 'The Foxglove Armchair' ],
		'shortDescription' => [ 'fa' => 'یک صندلی تک‌نفره با پشتیِ بلند و پیچیده، مناسب گوشه مطالعه و گفتگوهای طولانی.', 'en' => 'A single seat with a high, wrapped back for reading corners and long conversations.' ],
		'description'      => [
			'fa' => [
				'فاکسگلاو پشتی بلندی را دور شانه‌های نشسته می‌پیچد؛ شکلی وام‌گرفته از صندلی‌های بالدار اما بدون حجم آن‌ها. دسته‌ها کمی پایین‌تر از نشیمن قرار می‌گیرند و جایی برای گذاشتن یک کتاب یا پتو کنار کوسن باقی می‌گذارند.',
				'این صندلی روی همان قاب گردویی آورلیا ساخته می‌شود و بسته به فضایی که برایش در نظر گرفته شده، هم‌رنگ یا در تضاد با آن تمام می‌شود.',
			],
			'en' => [
				"Foxglove wraps a high back around the sitter's shoulders, a shape borrowed from wingback chairs but built without their bulk. The arms sit slightly lower than the seat, leaving room to tuck a book or a blanket beside the cushion.",
				"It's built on the same walnut frame as the Aurelia, finished to match or contrast depending on the room it's going into.",
			],
		],
		'dimensions'       => [ 84, 88, 108 ],
	],
	[
		'slug'             => 'wren-armchair',
		'category'         => 'armchairs',
		'featured'         => false,
		'price'            => 112000000,
		'photos'           => [ 14, 7 ],
		'name'             => [ 'fa' => 'صندلی راحتی رن', 'en' => 'The Wren Armchair' ],
		'shortDescription' => [ 'fa' => 'صندلی‌ای کوتاه با دسته‌های باز، مناسب اتاق‌های کوچک و صندلی دوم کنار میز.', 'en' => 'A low, open-arm chair for small rooms and second seats at a table.' ],
		'description'      => [
			'fa' => [
				'قاب رن از هر دو طرف باز می‌ماند، به همین دلیل سبک‌تر از فاکسگلاو به‌نظر می‌رسد و در اتاق‌هایی که یک صندلی بالدار کامل شلوغشان می‌کند، جا می‌شود. این صندلی بیشتر از همه به‌صورت جفت سفارش داده می‌شود.',
				'ارتفاع نشیمن با میزهای غذاخوری و میزهای کناری استاندارد هم‌خوانی دارد و همین آن را به همان اندازه صندلی همراه، عملی می‌کند که تکی.',
			],
			'en' => [
				"Wren keeps the frame open on both sides, so it reads lighter than the Foxglove and fits into rooms where a full wingback would crowd the corner. It's the chair most often ordered in pairs.",
				"The seat height matches standard dining and side tables, which makes it a practical second chair as much as an armchair on its own.",
			],
		],
		'dimensions'       => [ 78, 82, 86 ],
	],
	[
		'slug'             => 'meridian-lounge-chair',
		'category'         => 'lounge-chairs',
		'featured'         => true,
		'price'            => 184000000,
		'photos'           => [ 8, 9, 10 ],
		'name'             => [ 'fa' => 'صندلی لانژ مریدین', 'en' => 'The Meridian Lounge Chair' ],
		'shortDescription' => [ 'fa' => 'صندلی لانژی با تکیه‌گاه عمیق، مناسب گوشه پنجره‌ها و بعدازظهرهای بی‌عجله.', 'en' => 'A deep-recline lounge chair for corner windows and unhurried afternoons.' ],
		'description'      => [
			'fa' => [
				'مریدین پشتی خود را چند درجه بیشتر از یک صندلی راحتی معمولی خم می‌کند، آنقدر که بتوان بدون زیرپایی به‌طور کامل روی آن دراز کشید. قاب در پایه پهن بریده می‌شود تا پایداری داشته باشد و به سمت تکیه‌گاه سر باریک‌تر می‌شود.',
				'این صندلی به‌جای پنل‌های جداگانه، به‌صورت یک قطعه پیوسته رویه‌دوزی می‌شود؛ بنابراین در خط شانه، جایی که بدن بیشترین زمان را روی آن می‌گذراند، هیچ درزی وجود ندارد.',
			],
			'en' => [
				"Meridian tilts its back several degrees further than a standard armchair, enough to hold a full recline without a footstool. The frame is cut wide at the base for stability, then tapers toward the headrest.",
				"It's upholstered as a single continuous piece rather than separate panels, so there's no seam across the shoulder line where the body rests longest.",
			],
		],
		'dimensions'       => [ 88, 100, 102 ],
	],
	[
		'slug'             => 'solace-lounge-chair',
		'category'         => 'lounge-chairs',
		'featured'         => false,
		'price'            => 156000000,
		'photos'           => [ 11, 12 ],
		'name'             => [ 'fa' => 'صندلی لانژ سولاس', 'en' => 'The Solace Lounge Chair' ],
		'shortDescription' => [ 'fa' => 'صندلی لانژی باریک‌تر با پشتی مستقیم‌تر، مناسب مطالعه نه خواب.', 'en' => 'A narrower lounge chair with a straighter back, for reading rather than sleeping.' ],
		'description'      => [
			'fa' => [
				'در حالی که مریدین برای دراز کشیدن کامل ساخته شده، سولاس زاویه‌ای عمودی‌تر دارد و برای مطالعه یا کار کردن روی صندلی مناسب‌تر است تا دراز کشیدن.',
				'قاب آن به‌اندازه‌ای باریک است که کنار تخت یا در اتاق مطالعه جا می‌شود بدون آنکه بر فضا مسلط شود، در حالی که همان عمق کوسن مجموعه لانژ را حفظ می‌کند.',
			],
			'en' => [
				"Where the Meridian is built for a full recline, Solace holds a more upright angle, better suited to reading or working from a chair rather than lying back in one.",
				"The frame is narrow enough to fit beside a bed or in a study without dominating the room, while keeping the same depth of cushion as the rest of the lounge line.",
			],
		],
		'dimensions'       => [ 76, 92, 98 ],
	],
	[
		'slug'             => 'kessler-coffee-table',
		'category'         => 'tables',
		'featured'         => false,
		'price'            => 84000000,
		'photos'           => [ 13, 14 ],
		'name'             => [ 'fa' => 'میز جلومبلی کسلر', 'en' => 'The Kessler Coffee Table' ],
		'shortDescription' => [ 'fa' => 'میزی کوتاه از چوب بلوط با یک اتصال قابل‌مشاهده در هر گوشه.', 'en' => 'A low oak table with a single visible joint at each corner.' ],
		'description'      => [
			'fa' => [
				'کسلر از همان بلوط کهن‌رشدی ساخته می‌شود که قاب صندلی‌های کنارش از آن ساخته شده‌اند؛ در هر گوشه با یک اتصال چوبی نمایان به‌جای پیچ‌های پنهان به هم متصل می‌شود.',
				'رویه میز، جایی که رگه‌های چوب اجازه دهد، از یک تخته واحد بریده می‌شود تا سطح به‌جای چند تخته چسبانده‌شده کنار هم، یک قطعه پیوسته چوب به‌نظر برسد.',
			],
			'en' => [
				"Kessler is built from the same old-growth oak as the frames it sits beside, joined at each corner with a single exposed mortise-and-tenon rather than hidden fasteners.",
				"The top is cut from one board where the grain allows, so the surface reads as a continuous piece of wood rather than several boards glued edge to edge.",
			],
		],
		'dimensions'       => [ 120, 65, 38 ],
	],
	[
		'slug'             => 'amara-side-table',
		'category'         => 'tables',
		'featured'         => false,
		'price'            => 39200000,
		'photos'           => [ 7, 8 ],
		'name'             => [ 'fa' => 'میز کناری آمارا', 'en' => 'The Amara Side Table' ],
		'shortDescription' => [ 'fa' => 'میزی کوچک و گرد، مناسب کنار صندلی یا تخت.', 'en' => 'A small round table for beside a chair or a bed.' ],
		'description'      => [
			'fa' => [
				'آمارا به‌جای اتصال، تراش داده می‌شود؛ پایه‌ای تکی زیر رویه‌ای گرد و به‌اندازه‌ای که یک چراغ و یک کتاب را جا دهد. این کوچک‌ترین قطعه مجموعه است و بیشتر از همه به‌صورت جفت خریداری می‌شود.',
				'رنگ‌بندی آن با هر تن چوبی که از قبل در اتاق وجود دارد هماهنگ می‌شود، چرا که به‌ندرت اولین قطعه‌ای است که مشتری سفارش می‌دهد.',
			],
			'en' => [
				"Amara is turned rather than joined, a single leg base under a round top just wide enough for a lamp and a book. It's the smallest piece in the collection and the one most often bought in twos.",
				"Finished to match any wood tone already in the room, since it's rarely the first piece a client orders.",
			],
		],
		'dimensions'       => [ 42, 42, 52 ],
	],
	[
		'slug'             => 'bramwell-sideboard',
		'category'         => 'storage',
		'featured'         => false,
		'price'            => 244000000,
		'photos'           => [ 9, 10, 11 ],
		'name'             => [ 'fa' => 'بوفه برمول', 'en' => 'The Bramwell Sideboard' ],
		'shortDescription' => [ 'fa' => 'بوفه‌ای بلند و کوتاه با لولاهای پنهان و قفسه‌بندی داخلی قابل‌تنظیم.', 'en' => 'A long, low sideboard with hidden hinges and adjustable interior shelving.' ],
		'description'      => [
			'fa' => [
				'برمول در تمام عرض یک دیوار غذاخوری کشیده می‌شود؛ درهایش روی لولاهایی نصب شده‌اند که کاملاً در قاب فرو رفته‌اند تا چیزی سطح جلویی را قطع نکند. قفسه‌های داخلی در پله‌های دو سانتی‌متری قابل‌تنظیم‌اند.',
				'همان بلوط کهن‌رشدی که در سراسر مجموعه استفاده می‌شود، در سطوح داخلی نیز ادامه می‌یابد، نه فقط بخش‌هایی که دیده می‌شوند — جزئیاتی که اغلب بوفه‌ها از آن صرف‌نظر می‌کنند.',
			],
			'en' => [
				"Bramwell runs the full width of a dining wall, its doors hung on hinges set flush into the frame so nothing interrupts the front face. Inside, the shelves adjust in two-centimeter increments.",
				"The same old-growth oak used throughout the collection continues on the interior surfaces, not just the parts that show — a detail most sideboards skip.",
			],
		],
		'dimensions'       => [ 240, 48, 82 ],
	],
];

$created = 0;
$skipped = 0;

foreach ( $products as $product ) {
	if ( get_page_by_path( $product['slug'], OBJECT, 'product' ) ) {
		WP_CLI::log( "  {$product['slug']}: already exists, skipping." );
		++$skipped;
		continue;
	}

	$post_id = wp_insert_post(
		[
			'post_type'    => 'product',
			'post_title'   => $product['name']['fa'],
			'post_excerpt' => $product['shortDescription']['fa'],
			'post_status'  => 'publish',
			'post_name'    => $product['slug'],
		],
		true
	);

	if ( is_wp_error( $post_id ) ) {
		WP_CLI::warning( "  {$product['slug']}: {$post_id->get_error_message()}" );
		continue;
	}

	set_post_thumbnail( $post_id, $product['photos'][0] );
	$gallery_source->save_attachment_ids( $post_id, $product['photos'] );
	wp_set_object_terms( $post_id, $product['category'], 'product_category' );

	update_field( 'title_en', $product['name']['en'], $post_id );
	update_field( 'short_description_en', $product['shortDescription']['en'], $post_id );
	update_field( 'price', $product['price'], $post_id );
	update_field( 'featured', $product['featured'], $post_id );

	update_field(
		'dimensions',
		[
			'width'  => $product['dimensions'][0],
			'height' => $product['dimensions'][1],
			'depth'  => $product['dimensions'][2],
			'unit'   => 'cm',
		],
		$post_id
	);

	update_field(
		'description_fa',
		array_map( fn( $p ) => [ 'paragraph' => $p ], $product['description']['fa'] ),
		$post_id
	);
	update_field(
		'description_en',
		array_map( fn( $p ) => [ 'paragraph' => $p ], $product['description']['en'] ),
		$post_id
	);

	if ( ! empty( $product['finishes'] ) ) {
		update_field( 'finishes', $product['finishes'], $post_id );
	}
	if ( ! empty( $product['sizes'] ) ) {
		update_field( 'sizes', $product['sizes'], $post_id );
	}

	WP_CLI::log( "  {$product['slug']}: created (post_id {$post_id})." );
	++$created;
}

WP_CLI::success( "Seeded catalog: {$created} created, {$skipped} already existed." );
