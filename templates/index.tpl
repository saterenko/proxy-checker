<!DOCTYPE html>
<html>
<head>
    <title>{{system-name}} :: admin</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="/css/uikit.min.css?15">
    <link rel="stylesheet" href="/css/main.css?15">
    <script src="/js/jquery-3.1.1.min.js?15"></script>
    <script src="/js/uikit.min.js?15"></script>
</head>
<body>

    <nav class="uk-navbar">
        <a class="uk-navbar-brand" href="/">{{system-name}}</a>
    </nav>

    <div class="uk-container uk-margin">
        <ul class="uk-breadcrumb">{{BEGIN breadcrumbs}}{{IF url}}<li><a href="{{url}}">{{title}}</a></li>{{ELSE}}<li{{IF last}} class="uk-active"{{END}}><span>{{title}}</span></li>{{END}}{{END}}</ul>
    </div>

    <div class="uk-container">
        {{IF errors}}<div class="uk-alert uk-alert-danger uk-width-1-3 uk-container-center"><ul>{{BEGIN errors}}<li>{{error}}</li>{{END}}</ul></div>{{END}}
        {{content}}
    </div>
    <br><br>

</body>
</html>

