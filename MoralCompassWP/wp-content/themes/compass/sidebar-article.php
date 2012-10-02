<aside class="sidebar article-sidebar left">
    <section class=article-index>
        <h6>CONTENTS</h6>
        <ol>
            <li>1) Introduction</li>
            <li>2) Resources</li>
            <li>3) Scorecard</li>
            <li>4) Comments</li>
            <li>5) Bibliography/Links</li>
        </ol>
    </section>

    <section class=your-scorecard>
        <h6 class=padded-title>YOUR SCORECARD</h6>
		<table cellpadding=0 cellspacing=0 class="score-table no-bottom">
		<?php 
			global $userScoreCardItems;
			foreach ($userScoreCardItems as $scoreCardItem)
			{
				echo "<tr>";
				echo "<th class=left-header>" . $scoreCardItem->who . "</th>";
				echo "<td class=grade>" . "<div class='" . $scoreCardItem->scoreToClass() . "'>" . $scoreCardItem->scoreToGrade() . "</div></td>";
				echo "</tr>";
				echo "<tr>";
				if ($scoreCardItem->id != -1 && !empty($scoreCardItem->description))
					echo "<td colspan=2 class='left-description'><div class=description-text>" . $scoreCardItem->description . "</div></td>";
				else
					echo "<td colspan=2 class='left-description'><div class=description-text>&nbsp;</div></td>";
				
				echo "</tr>";
			}
		?>
		</table>
		<?php if (!is_user_logged_in()) : ?>
            <p class="controls no-bottom clearfix">
				<a href="/wp-login.php" ><img src="<?php echo ABSURL ?>wp-content/themes/compass/img/log-in.png"></img></a>
				<br/>
				<a href="/wp-register.php" class=register-left>(or register to get started)</a>
            </p>
		<?php else: ?>
		
            <p class="controls no-bottom clearfix">
                <button id=expand-scoreboard2 class="expand left" onclick="javascript:setState(4)">Add/Change Grades</button>
            </p>
         <?php endif; ?>
    </section>
</aside>