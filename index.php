<?php get_header(); ?>

hello world....

  <?php if ( have_posts() ) :?>
    <div class="interior-container">
    	<div class="inner_banner"  style="background:url(<?php echo get_field('banner_image',13);?>) no-repeat center top;">
            <div class="container ">
		<div class="blog_header">
                    <h1 class="pattern">
                        <span><img class="left" src="<?php echo get_template_directory_uri();?>/images/w-pattern1.png"></span>
                        <?php the_field('banner_title',13);?>
                        <span><img class="right" src="<?php echo get_template_directory_uri();?>/images/w-pattern2.png"></span>
                    </h1>
                    <p><?php the_field('banner_content',13);?></p>
                </div>	
            </div>
            <a href="#middle"><span class="header_arrow"><img src="<?php echo get_template_directory_uri();?>/images/header-arrow.png"></span></a>
       	</div>
	<div class="blog-interior-content" id="middle">
            <div class="container">
               	<div class="left">
                    <?php while ( have_posts() ) : the_post(); ?>
			<div class="blog_main">
			<?php if(has_post_thumbnail()):
                            $featured_img_url=wp_get_attachment_image_src(get_post_thumbnail_id(),'blog-main');?>
                            <img src="<?php if(has_post_thumbnail()): echo $featured_img_url[0]; else: $feature_img_url; endif;?>" />
                        <?php endif;?>
                        <a href="<?php the_permalink();?>"><h2><?php the_title();?></h2></a>
			<?php if( strpos($post->post_content, '<!--more-->') >= 1 ) {
                            the_content('Read more &rarr;');
                        } else {
			    vtl_smart_excerpt(300);
                        }?>
                        <a href="<?php the_permalink();?>" class="readarticle">READ ARTICLE</a>
                      	<div class="social">
                            <?php $comments_count = wp_count_comments($post->ID);
				$number = $comments_count->total_comments;
				if ($number > 0) { ?>
				    <div class="comment">
					<?php  // Anything less than a million
					$n_format = number_format($number);
					echo $n_format.' comment(s)'; ?> 
				    </div>
				<?php } else {
				    echo'';
				}?>
                                <div class="blog_tag">
                                    <?php the_tags('<ul><li>','</li><li>','</li></ul>'); ?>
                                </div>
                                <div class="social_share">
                                    <?php get_template_part('parts/share-blog');?>
                                </div>
                            </div>
			</div>
                    <?php endwhile; ?>
                    <div class="pagination">
			<?php pagination(); ?>
                    </div>
		</div> 
		
		<div class="right">
		    <?php get_template_part('parts/blog-sidebar');?>
		</div>
	    </div>
	</div>
        <div class="hr-line"></div>
	    <div class="blog-gallery">
		<div class="container">
		    <div class="gallery-block">
			<?php get_template_part('parts/gallery-block');?>
		    </div>
		</div>
	    </div>
	</div>
    <?php endif; ?>
<?php get_footer(); ?>
