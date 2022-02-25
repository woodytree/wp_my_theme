<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

function get_page_link_by_template( $template ) {
	
    $pages = get_pages( array(
		'post_type'  => 'page',
        'meta_key' 	 => '_wp_page_template',
        'meta_value' => $template
    ) );
	
    if ( isset( $pages[0] ) ) {
        return get_page_link( $pages[0]->ID );
    }
	
}

// this is to add a 'fake' component to BuddyPress. A registered component is needed to add notifications
function blackjack_notifications_component( $components = array() ) {
	
	// Force $components to be an array
	if ( !is_array( $components ) ) {
		$components = array();
	}
	
	// Add 'blackjack' component to the registered components array
	array_push( $components, 'blackjack' );
	
	// Return components with 'blackjack' appended
	return $components;
	
}
add_filter( 'bp_notifications_get_registered_components', 'blackjack_notifications_component' );

// this gets the saved game id, compiles some data and then displays the notification
function blackjack_notifications( $content, $game_id, $secondary_item_id, $total_items, $format = 'string', $action, $component ) {
	
	if ( 'game_result' === $action ) {
		
		global $wpdb;

		if ( $game = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM wp_blackjack_games WHERE id = %d', $game_id ) ) ) {
			
			$result1 = (int) $game->result1;
			$player1 = (int) $game->player1;
			$player2 = (int) $game->player2;
			$points1 = (int) $game->points1;
			$points2 = (int) $game->points2;
			$bet     = (int) $game->bet;
			
			$points21 = '21 очко';
			$points1 = $points1 != 21 ? $points1 : $points21;
			$points2 = $points2 != 21 ? $points2 : $points21;
			
			$text = 'Результат игры в BlackJack! ';
			
			$url = get_page_link_by_template( 'blackjack.php' );
			$link = '<b><a href="'. $url .'">Играть еще?</a></b>';
			
			$player1 = function_exists( 'bp_core_get_userlink' ) ? bp_core_get_userlink( $player1 ) : get_user_by( 'id', $player1 )->user_login;
			
			if ( $result1 == 1 ) {
				$text .= 'Вы проиграли <b>'. $bet .'</b> <b>'. $player1 .'</b> '.
				        '(у Вас <b>'. $points2 .'</b>, у <b>'. $player1 .'</b> <b>'. $points1 .'</b>). '.
						$link;
			} elseif ( $result1 == 2 ) {
				$text .= 'Вы выйграли <b>'. $bet .'</b> у <b>'. $player1 .'</b> '.
						'(у Вас <b>'. $points2 .'</b>, у <b>'. $player1 .'</b> <b>'. $points1 .'</b>). '.
						$link;
			} else {
				$text .= 'Вашим соперником был <b>'. $player1 .'</b> '.
						'(у Вас и у <b>'. $player1 .'</b> <b>'. $points1 .'</b>), победила дружба! '.
						$link;
			}
			
			if ( 'string' === $format ) { // return for notifications widget
				$return = $text;
			} else { 					  // for bar
				$return = array(
					'text' => strip_tags( $text ),
					'link' => $url
				);
			}
			
			return $return;
			
		}
		
	}
	
}
add_filter( 'bp_notifications_get_notifications_for_user', 'blackjack_notifications', 10, 7 );

// this adds bp notification
function blackjack_game_result_add_notification( $user_id, $game_id ) {
	
    if ( function_exists( 'bp_notifications_add_notification' ) && bp_is_active( 'notifications' ) ) {
		
    	bp_notifications_add_notification( array(
            'user_id'           => $user_id, // user to whom notification has to be sent
            'item_id'           => $game_id,  // id of the game to notify about
            'component_name'    => 'blackjack', // 'fake' registered component
            'component_action'  => 'game_result', // notification format action
            'date_notified'     => bp_core_current_time(), // current time
            'is_new'            => 1 // notification is new and unread
        ) );
		
    }
	
}
add_action( 'blackjack_game_result_notification', 'blackjack_game_result_add_notification', 10, 2 );
/**
* blackjack_game_result_notification is the action which will be called by do_action()
* blackjack_game_result_add_notification is the function called with the action
* 10 is priority number
* 2 is number of parameters
*/

function blackjack_start_echo() {
	
	echo('<h2>BlackJack</h2>');
	echo('<table width=\'100%\' cellspacing=0 cellpadding=10>');
	echo('<tr><td>');
	echo('<center><table cellspacing=0 cellpadding=5>');
	echo('<tr><td><h3>Здравствуй, <b>'. ( function_exists( 'bp_core_get_userlink' ) ? bp_core_get_userlink( get_current_user_id() ) : wp_get_current_user()->user_login ) .'</b>. Готов просадить свои бонусы?!<br />(баланс '. (int) get_user_meta( get_current_user_id(), 'bonus', true ) .')</h3></td></tr>');
	echo('<tr><td class=img><img src='. get_stylesheet_directory_uri() .'/assets/images/cards/teacup.jpg style=\'height: 250px\' title=\'BlackJack\' border=0></td></tr>');
	echo('<tr><td><h3>Инструкция</h3>Вы должны набрать большее количество очков, чем у оппонента, но не более <b>21</b>.</td></tr>');
	echo('<tr><td>'
	.'<label><input type=radio name=bet value=\'1\'/>&nbsp;10&nbsp;</label>&nbsp;[<b onClick=\'blackjackAction("count", 1, this);\' title=\'Доступные игры с данной ставкой\' style=\'cursor: pointer;\'>&nbsp;?&nbsp;</b>]&nbsp;&nbsp;&nbsp;'
	.'<label><input type=radio name=bet value=\'2\'/>&nbsp;50&nbsp;</label>&nbsp;[<b onClick=\'blackjackAction("count", 2, this);\' title=\'Доступные игры с данной ставкой\' style=\'cursor: pointer;\'>&nbsp;?&nbsp;</b>]&nbsp;&nbsp;&nbsp;'
	.'<label><input type=radio name=bet value=\'3\'/>&nbsp;100&nbsp;</label>&nbsp;[<b onClick=\'blackjackAction("count", 3, this);\' title=\'Доступные игры с данной ставкой\' style=\'cursor: pointer;\'>&nbsp;?&nbsp;</b>]&nbsp;&nbsp;&nbsp;'
	.'<label><input type=radio name=bet value=\'4\'/>&nbsp;500&nbsp;</label>&nbsp;[<b onClick=\'blackjackAction("count", 4, this);\' title=\'Доступные игры с данной ставкой\' style=\'cursor: pointer;\'>&nbsp;?&nbsp;</b>]&nbsp;&nbsp;&nbsp;'
	.'<label><input type=radio name=bet value=\'5\'/>&nbsp;1000&nbsp;</label>&nbsp;[<b onClick=\'blackjackAction("count", 5, this);\' title=\'Доступные игры с данной ставкой\' style=\'cursor: pointer;\'>&nbsp;?&nbsp;</b>]&nbsp;&nbsp;&nbsp;'
	.'</td></tr>');
	echo('<tr><td><input type=button class=btn onClick=\'jSwitch(this); blackjackAction("start");\' value=\'Сдать!\'/></td></tr>');
	echo('</td></tr></table></center>');
	echo('</td></tr>');
	echo('</table>');
	
}
add_action( 'blackjack_start', 'blackjack_start_echo', 10, 0 );

function blackjack_game_result( $result1, $game1_id, $game2_id, $player1, $player2, $points1, $points2, $bet ) {
	
	$bonus1 = (int) get_user_meta( $player1, 'bonus', true );
	
	$bonus2 = (int) get_user_meta( $player2, 'bonus', true );
	
	if ( $result1 == 1 ) {
		
		update_user_meta( $player1, 'bonus', $bonus1 + $bet );
		
		update_user_meta( $player2, 'bonus', $bonus2 - $bet );
		
		$result = array(
			'text'  => 'Вы выйграли '. $bet,
			'color' => 'green'
		);
		
	} elseif ( $result1 == 2 ) {
		
		update_user_meta( $player1, 'bonus', $bonus1 - $bet );
		
		update_user_meta( $player2, 'bonus', $bonus2 + $bet );
		
		$result = array(
			'text'  => 'Вы проиграли '. $bet,
			'color' => 'red'
		);
		
	} else {
		
		$result = array(
			'text'  => 'ничья',
			'color' => 'blue'
		);
		
	}
	
	global $wpdb;
	
	$wpdb->insert( 'wp_blackjack_games', array(
		'result1' => $result1,
		'player1' => $player1,
		'player2' => $player2,
		'points1' => $points1,
		'points2' => $points2,
		'bet'     => $bet
	) );
	
	do_action( 'blackjack_game_result_notification', $player2, $wpdb->insert_id );
	
	$wpdb->delete( 'wp_blackjack', array( 'id' => $game1_id ) );
	
	$wpdb->delete( 'wp_blackjack', array( 'id' => $game2_id ) );
	
	return $result;
	
}

function blackjack_ajax_handler() {

	$blackjack = $_POST['blackjack_action'];

	if ( $blackjack ) {
		
		if ( $blackjack == 'start' || $blackjack == 'count' ) {
				$bet = $_POST['blackjack_bet'];
			if ( $bet == '1' )
				$bet = 10; // 10
			elseif ( $bet == '2' )
				$bet = 50; // 50
			elseif ( $bet == '3' )
				$bet = 100; // 100
			elseif ( $bet == '4' )
				$bet = 500; // 500
			elseif ( $bet == '5' )
				$bet = 1000; // 1000
			else {
				wp_die('<b><font color=\'red\'>Критическая ошибка! Не удалось получить данные о ставке.</font></b>&nbsp;<input type=button class=btn onClick=\'jSwitch(this); blackjackAction("tostart");\' value=\'Обновить\'/>');
			}
		}
		
		global $wpdb;
		
		$cardcount = $wpdb->get_var( 'SELECT COUNT(id) FROM wp_blackjack_cards' );
		
		$cardfolder = get_stylesheet_directory_uri() .'/assets/images/cards/';
		
		$cardformat = '.png';
		
		$player = get_current_user_id();
		
		if ( $blackjack == 'start' ) {
			
			if( ( $bonus = (int) get_user_meta( $player, 'bonus', true ) ) <= 0 || $bonus > 0 && $bonus < $bet )
				wp_die('<b><font color=\'red\' style=\'align: center;\'>Ошибка! У Вас недостаточно бонусов для выбранной ставки ('. $bonus .' < '. $bet .').</font></b>&nbsp;<input type=button class=btn onClick=\'jSwitch(this); blackjackAction("tostart");\' value=\'Обновить\'/>');
			
			if ( $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM wp_blackjack WHERE player = %d AND status = \'playing\'', $player ) ) > 0 ) {
				
				$game = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM wp_blackjack WHERE player = %d AND status = \'playing\'', $player ) );
				
				wp_die('<b><font color=\'red\' style=\'align: center;\'>Ошибка! Вы ещё не завершили предыдущую игру (очки = '. (int) $game->points .'; ставка '. (int) $game->bet .').</font></b>&nbsp;<input type=button class=btn onClick=\'jSwitch(this); blackjackAction("cont");\' value=\'Продолжить?!\'/>&nbsp;<input type=button class=btn onClick=\'jSwitch(this); blackjackAction("stop");\' value=\'Завершить!\'/>&nbsp;<input type=button class=btn onClick=\'jSwitch(this); blackjackAction("tostart");\' value=\'Обновить\'/>');
				
			} else {
				
				if ( $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM wp_blackjack WHERE player = %d AND status = \'waiting\' AND bet = %d', $player, $bet ) ) > 0 ) {
				
					$game = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM wp_blackjack WHERE player = %d AND status = \'waiting\' AND bet = %d', $player, $bet ) );
					
					wp_die('<b><font color=\'red\' style=\'align: center;\'>Ошибка! Вы должны дождаться, пока кто-нибудь не сыграет с Вами (очки = '. (int) $game->points .'; ставка '. (int) $game->bet .').</font></b>&nbsp;<input type=button class=btn onClick=\'jSwitch(this); blackjackAction("tostart");\' value=\'Обновить\'/>');
				
				}
				
			}
			
			$cardid = rand( 1, $cardcount );
			
			$card = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM wp_blackjack_cards WHERE id = %d', $cardid ) );
			
			$wpdb->insert( 'wp_blackjack', array(
				'player' => $player,
				'points' => $card->points,
				'cards'  => $cardid,
				'bet'    => $bet
			) );
			
			echo('<h2>BlackJack</h2>');
			echo('<table width=\'100%\' cellspacing=0 cellpadding=10>');
			echo('<tr><td>');
			echo('<center><table cellspacing=0 cellpadding=5>');
			echo('<tr><td class=img><img src='. $cardfolder . $card->pic . $cardformat .' border=0></td></tr>');
			echo('<tr><td><b>Очки = '. $card->points .'</b></td></tr>');
			echo('<tr><td><input type=button class=btn onClick=\'jSwitch(this); blackjackAction("cont");\' title=\'Нажмите, чтобы продолжить игру.\' value=\'ещё давай!\'/></td></tr>');
			echo('</td></tr></table></center>');
			echo('</td></tr>');
			echo('</table>');
			
			wp_die();
			
		} elseif ( $blackjack == 'cont' ) {
			
			$game = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM wp_blackjack WHERE player = %d AND status = \'playing\'', $player ) );
			
			if ( !$game )
				wp_die('<b><font color=\'red\'>Ошибка! Вы не можете продолжить ещё не начатую игру.</font></b>&nbsp;<input type=button class=btn onClick=\'jSwitch(this); blackjackAction("tostart");\' value=\'Обновить\'/>');
			
			$cards = explode( ',', $game->cards );
			
			$showcards = '';
			
			foreach ( $cards as $cardid ) {
				
				$card = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM wp_blackjack_cards WHERE id = %d', $cardid ) );
				
				$showcards .= '<img src='. $cardfolder . $card->pic . $cardformat .' border=0 style=\'margin-right: 10px;\'>';
				
			}
			
			$cardid = rand( 1, $cardcount );
			
			while ( in_array( $cardid, $cards ) )
				$cardid = rand( 1, $cardcount );
			
			$card = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM wp_blackjack_cards WHERE id = %d', $cardid ) );
			
			$showcards .= '<img src='. $cardfolder . $card->pic . $cardformat .' border=0 style=\'margin-right: 10px;\'>';
			
			$points = $game->points + $card->points;
			
			$cards = $game->cards .','. $cardid;
			
			$wpdb->update( 'wp_blackjack', array(
				'points' => $points,
				'cards'  => $cards
			), array(
				'id'     => $game->id
			) );
			
			$bet = $game->bet;
			
			if ( $points == 21 ) {
				
				if ( $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM wp_blackjack WHERE player != %d AND status = \'waiting\' AND bet = %d', $player, $bet ) ) > 0 ) {
				
					$game2 = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM wp_blackjack WHERE player != %d AND status = \'waiting\' AND bet = %d ORDER BY RAND() LIMIT 1', $player, $bet ) );
					
					$player2 = $game2->player;
					
					$points2 = $game2->points;
					
					if ( $points2 != 21 ) {
						
						$result = blackjack_game_result( 1, $game->id, $game2->id, $player, $player2, $points, $points2, $bet );
						
					} else {
						
						$result = blackjack_game_result( 3, $game->id, $game2->id, $player, $player2, $points, $points2, $bet );
						
					}
					
					wp_die('<b><font color=\''. $result['color'] .'\' style=\'align: center;\'>Игра окончена! у Вас 21 очко, Вашим оппонентом был '. ( function_exists( 'bp_core_get_userlink' ) ? bp_core_get_userlink( $player2 ) : get_user_by( 'id', $player2 )->user_login ) .', у него '. $points2 .', '. $result['text'] .'.</font></b>&nbsp;<input type=button class=btn onClick=\'jSwitch(this); blackjackAction("tostart");\' value=\'Играть ещё?\'/>');
				
				} else {
					
					$wpdb->update( 'wp_blackjack', array( 'status' => 'waiting' ), array( 'id' => $game->id ) );
					
					wp_die('<b><font color=\'blue\' style=\'align: center;\'>Игра окончена! у Вас 21 очко, пока что нет других игроков. Вам придётся подождать соперника. Вы получите уведомление с результатами игры.</font></b>&nbsp;<input type=button class=btn onClick=\'jSwitch(this); blackjackAction("tostart");\' value=\'Обновить\'/>');
				
				}
				
			} elseif ( $points > 21 ) {
				
				if ( $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM wp_blackjack WHERE player != %d AND status = \'waiting\' AND bet = %d', $player, $bet ) ) > 0 ) {
					
					$game2 = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM wp_blackjack WHERE player != %d AND status = \'waiting\' AND bet = %d ORDER BY RAND() LIMIT 1', $player, $bet ) );
					
					$player2 = $game2->player;
					
					$points2 = $game2->points;
					
					if ( $points2 == $points ) {
						
						$result = blackjack_game_result( 3, $game->id, $game2->id, $player, $player2, $points, $points2, $bet );
						
					} elseif ( $points2 > $points ) {
						
						$result = blackjack_game_result( 1, $game->id, $game2->id, $player, $player2, $points, $points2, $bet );
						
					} else { // $points2 < $points
						
						$result = blackjack_game_result( 2, $game->id, $game2->id, $player, $player2, $points, $points2, $bet );
						
					}
					
					wp_die('<b><font color=\''. $result['color'] .'\' style=\'align: center;\'>Игра окончена! у Вас '. $points .', Вашим оппонентом был '. ( function_exists( 'bp_core_get_userlink' ) ? bp_core_get_userlink( $player2 ) : get_user_by( 'id', $player2 )->user_login ) .', у него '. $points2 .', '. $result['text'] .'.</font></b>&nbsp;<input type=button class=btn onClick=\'jSwitch(this); blackjackAction("tostart");\' value=\'Играть ещё?\'/>');
				
				} else {
					
					$wpdb->update( 'wp_blackjack', array( 'status' => 'waiting' ), array( 'id' => $game->id ) );
					
					wp_die('<b><font color=\'blue\' style=\'align: center;\'>Игра окончена! у Вас '. $points .', пока что нет других игроков. Вам придётся подождать соперника. Вы получите уведомление с результатами игры.</font></b>&nbsp;<input type=button class=btn onClick=\'jSwitch(this); blackjackAction("tostart");\' value=\'Обновить\'/>');
				
				}
				
			} else {
				
				echo('<h2>BlackJack</h2>');
				echo('<table width=\'100%\' cellspacing=0 cellpadding=10>');
				echo('<tr><td>');
				echo('<center><table cellspacing=0 cellpadding=5>');
				echo('<tr><td class=img>'. $showcards .'</td></tr>');
				echo('<tr><td><b>Очки = '. $points .'</b></td></tr>');
				echo('<tr><td><input type=button class=btn onClick=\'jSwitch(this); blackjackAction("cont");\' value=\'ещё давай!\'/><br /><input type=button class=btn onClick=\'jSwitch(this); blackjackAction("stop");\' value=\'Хватит\'/></td></tr>');
				echo('</td></tr></table></center>');
				echo('</td></tr>');
				echo('</table>');
				
				wp_die();
				
			}
			
		} elseif ( $blackjack == 'stop' ) {
			
			$game = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM wp_blackjack WHERE player = %d AND status = \'playing\'', $player ) );
			
			if ( !$game )
				wp_die('<b><font color=\'red\'>Ошибка! Вы не можете продолжить ещё не начатую игру.</font></b>&nbsp;<input type=button class=btn onClick=\'jSwitch(this); blackjackAction("tostart");\' value=\'Обновить\'/>');
			
			$points = $game->points;
			
			$bet = $game->bet;
			
			if ( $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM wp_blackjack WHERE player != %d AND status = \'waiting\' AND bet = %d', $player, $bet ) ) > 0 ) {
				
				$game2 = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM wp_blackjack WHERE player != %d AND status = \'waiting\' AND bet = %d ORDER BY RAND() LIMIT 1', $player, $bet ) );
				
				$player2 = $game2->player;
				
				$points2 = $game2->points;
				
				if ( $points2 == $points ) {
					
					$result = blackjack_game_result( 3, $game->id, $game2->id, $player, $player2, $points, $points2, $bet );
					
				} elseif ( $points2 < $points && $points2 < 21 || $points2 > $points && $points2 > 21 ) {
					
					$result = blackjack_game_result( 1, $game->id, $game2->id, $player, $player2, $points, $points2, $bet );
					
				} elseif ( $points2 > $points && $points2 < 21 || $points2 == 21 || $points2 < $points && $points2 > 21 ) {
					
					$result = blackjack_game_result( 2, $game->id, $game2->id, $player, $player2, $points, $points2, $bet );
					
				}
				
				wp_die('<b><font color=\''. $result['color'] .'\' style=\'align: center;\'>Игра окончена! у Вас '. $points .', Вашим оппонентом был '. ( function_exists( 'bp_core_get_userlink' ) ? bp_core_get_userlink( $player2 ) : get_user_by( 'id', $player2 )->user_login ) .', у него '. $points2 .', '. $result['text'] .'.</font></b>&nbsp;<input type=button class=btn onClick=\'jSwitch(this); blackjackAction("tostart");\' value=\'Играть ещё?\'/>');
				
			} else {
				
				$wpdb->update( 'wp_blackjack', array( 'status' => 'waiting' ), array( 'id' => $game->id ) );
				
				wp_die('<b><font color=\'blue\' style=\'align: center;\'>Игра окончена! у Вас '. $points .', пока что нет других игроков. Вам придётся подождать соперника. Вы получите уведомление с результатами игры.</font></b>&nbsp;<input type=button class=btn onClick=\'jSwitch(this); blackjackAction("tostart");\' value=\'Обновить\'/>');
				
			}
			
		} elseif ( $blackjack == 'tostart' ) {
			
			do_action( 'blackjack_start' );
			
			wp_die();
			
		} elseif ( $blackjack == 'count' ) {
			
			$count = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM wp_blackjack WHERE player != %d AND status = \'waiting\' AND bet = %d', $player, $bet ) );
			
			wp_die('&nbsp;'. ( $count > 0 ? '<font color=\'green\'>'. $count .'</font>' : '<font color=\'red\'>0</font>') .'&nbsp;');
			
		} else {
			
			wp_die('<b><font color=\'red\'>Критическая ошибка! Неверное действие.</font></b>&nbsp;<input type=button class=btn onClick=\'jSwitch(this); blackjackAction("tostart");\' value=\'Обновить\'/>');
			
		}
		
	} else {
		
		wp_die('<b><font color=\'red\'>Критическая ошибка! Действие не найдено.</font></b>&nbsp;<input type=button class=btn onClick=\'jSwitch(this); blackjackAction("tostart");\' value=\'Обновить\'/>');
		
	}

}
add_action('wp_ajax_blackjack', 'blackjack_ajax_handler'); // wp_ajax_{action}, action is in js
