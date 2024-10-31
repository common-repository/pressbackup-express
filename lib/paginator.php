<?
	Class PressExpressPaginator {

		function get_html ($pagination = array()) {

			global $pressexpress;
			$output = null;

			if (($pagination['page'] < $pagination['pages']) &&($pagination['page']==1))
			{
				$pageNext=$pagination['page'] + 1;
				$next = $pagination['func_path']; $next[] .= $pageNext;
				$last = $pagination['func_path']; $last[] .= $pagination['pages'];

				$output=	"<span class=\"displaying-num\">".sprintf(__('Displaying %1$s - %2$s of %3$s.','pressbackup'), '1', $pagination['pagination'], $pagination['total'])."</span> ".
							$pressexpress->Html->link(__('Next','pressbackup'), $next, array('class'=>'button abpdding') )." ".
							$pressexpress->Html->link(__('Last','pressbackup'), $last, array('class'=>'button abpdding') );
			}
			elseif (($pagination['page'] < $pagination['pages']) &&($pagination['page'] >1))
			{
				$pageNext=$pagination['page'] + 1;
				$pagePrev=$pagination['page'] - 1;
				$first = $pagination['func_path']; $first[] .= '1';
				$next = $pagination['func_path']; $next[] .= $pageNext;
				$prev = $pagination['func_path']; $prev[] .= $pagePrev;
				$last = $pagination['func_path']; $last[] .= $pagination['pages'];

				$output =	"<span class=\"displaying-num\">".sprintf(__('Displaying %1$s - %2$s of %3$s.','pressbackup'), $pagination['ini'], $pagination['fin'], $pagination['total'])."</span> ".
								$pressexpress->Html->link(__('First','pressbackup'), $first, array('class'=>'button abpdding') )." ".
								$pressexpress->Html->link(__('Prev','pressbackup'), $prev, array('class'=>'button abpdding') )." ".
								$pressexpress->Html->link(__('Next','pressbackup'), $next, array('class'=>'button abpdding') )." ".
								$pressexpress->Html->link(__('Last','pressbackup'), $last, array('class'=>'button abpdding') );
			}
			elseif (($pagination['page'] == $pagination['pages']) &&($pagination['pages'] !=1))
			{
				$pagePrev=$pagination['page'] - 1;
				$first = $pagination['func_path']; $first[] .= '1';
				$prev = $pagination['func_path']; $prev[] .= $pagePrev;

				if ($pagination['fin'] > $pagination['total']) {$pagination['fin'] = $pagination['total'];}
				$output =	"<span class=\"displaying-num\">".sprintf(__('Displaying %1$s - %2$s of %3$s.','pressbackup'), $pagination['ini'], $pagination['fin'], $pagination['total'])."</span> ".
								$pressexpress->Html->link(__('First','pressbackup'), $first, array('class'=>'button abpdding') )." ".
								$pressexpress->Html->link(__('Prev','pressbackup'), $prev, array('class'=>'button abpdding') );
			}
			elseif(($pagination['pages'] == 1)&&($pagination['total']!=0))
			{
				$output = "<span class=\"displaying-num\">".sprintf(__('Displaying %1$s - %2$s of %3$s.','pressbackup'), '1', $pagination['total'], $pagination['total'])."</span>";
			}
			return $output;
		}
	}

?>
