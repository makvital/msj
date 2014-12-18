    <ul>
        <li>Share</li>
            <?php $featured_img_url=wp_get_attachment_image_src(get_post_thumbnail_id(),'blog-main'); ?>
            <li><a href="http://pinterest.com/pin/create/button/?url=<?php the_permalink();?>&media=<?php echo $featured_img_url[0];?>&description=<?php the_title();?>" target='_blank' data-pin-config="above"><span class="icon-pinterest"></span></a></li>
            <!--<li><a href="http://instagram.com" target='_blank'><span class="icon-uniF794"></span></a></li>-->
            <li><a href="https://www.facebook.com/sharer/share.php?u=<?php the_permalink();?>" target="_blank"><span class="icon-facebook"></span></a></li>
    </ul>