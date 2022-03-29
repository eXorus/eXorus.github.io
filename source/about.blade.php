---
title: About
description: A little bit about the site
---
@extends('_layouts.main')

@section('body')
    <h1>Vincent Dauce</h1>

    <img src="/assets/img/about.jpg"
        alt="Vincent Dauce Profile Picture"
        class="flex rounded-full h-64 w-64 bg-contain mx-auto md:float-right my-6 md:ml-10">

    <p class="mb-6">
        👋 Hey there, I'm Vincent, but you may know me better as <a href="https://twitter.com/exorus">@eXorus</a>. I live in Paris, France.
    </p>

    <p class="mb-6">
        I'm Quality Director at <a href="https://openclassrooms.com">OpenClassrooms</a>, where I drive the quality engineering team. Previously, I worked as Quality Manager at Veepee and inWebo.
    </p>

    <p class="mb-6">
        I'm also the creator of <a href="https://mailcare.io">MailCare</a>, an open source disposable email address services built with Laravel. And the primary maintainer of the <a href="https://github.com/php-mime-mail-parser/php-mime-mail-parser">php-mime-mail-parser library</a> with more than 2M downloads.
    </p>

    <p class="mb-6">
        When I'm not writing code, you'll find me spending time with my wife, my son.
    </p>

    <hr class="clear-left">
    <img src="/assets/img/amstrad.jpg"
        alt=""
        class="float-left h-32">
    
    <p class="mb-6 mx-8">
        I started coding in secondary school on an Amstrad with a little robot that answered my questions (didn't have to stray from the question I was expecting otherwise it didn't work 🙂 ) and a maze. We are in <strong>1996</strong>.
    </p>

    <hr class="clear-left">
    <img src="/assets/img/delphi.gif"
        alt=""
        class="float-left h-32">
    
    <p class="mb-6 mx-8">
        I later learned Pascal with Delphi to create library software for my video games and movies. We are in <strong>2000</strong>.
    </p>

    <hr class="clear-left">
    <img src="/assets/img/spexorus.info.png"
        alt=""
        class="float-left h-32">

    <p class="mb-6 mx-8">
        Around high school I discovered the Web and produced my first websites. First to exhibit my mother's works (artistad.over-blog.com), then my TPE on digital vs film photography and finally my first personal website (spexorus.info) which fortunately no longer exists. We are in <strong>2001</strong>.
    </p>

    <hr class="clear-left">
    <img src="/assets/img/evoxis.png"
        alt=""
        class="float-left h-32">

    <p class="mb-6 mx-8">
        Computer enthusiast I therefore joined an engineering school EFREI (School of Information and Management Technologies) following my BAC S. During my free time I developed a website/forum for a WoW RolePlay Community (evoxis.info) which no longer exists either but whose code was taken over for Khazaar. It was during these years that I learned PHP/MySQL, server administration under Debian... We are between <strong>2002 and 2007</strong>.
    </p>

    <hr class="clear-left">
    <img src="/assets/img/vinsmoinschers.com.jpg"
        alt=""
        class="float-left h-32">

    <p class="mb-6 mx-8">
        For my 4th year technical internship, I learned how to set up an online store with OSCommerce (now well dethroned by Prestashop), it was http://vinsmoinschers.com which still exists but has since been bought. We are in <strong>2006</strong>.
    </p>

@endsection
