<?php
/*
Template Name: Шаблон Личного кабинета
*/
get_header(); 

if ( ! is_user_logged_in() ) {
    wp_redirect( home_url( '/wp-login.php/' ) ); //перенаправление на страницу входа
    exit;
}

if ( in_array('administrator', (array) $current_user->roles) ) {
    // этот контент виден только администраторам
    echo 'Привет, Администратор!';
}
else if ( in_array('cinematographer', (array) $current_user->roles) ) {
    echo 'Вы зарегистрированы как КИНЕМАТОГРАФИСТ';
    ?> <br> <?php
    echo 'Теперь вы можете подать свой проект и найти спонсоров и инвесторов для его реализации';
}
else if ( in_array('investor', (array) $current_user->roles) ){
    echo 'Вы зарегистрированы как ИНВЕСТОР';
    ?> <br> <?php
    echo 'Теперь вы можете получать долю от прибыли кинопроекта';
}
else if ( in_array('sponsor', (array) $current_user->roles) ){
    echo 'Вы зарегистрированы как СПОНСОР';
    ?> <br> <?php
    echo 'Теперь вы можете помочь в реализации понравившейся идеи';
}

//для вывода шорткода
the_content();

?>