# Pandora 1.1 alpha

PHP framework for building web applications.

# Concept:
A fast and lightweight (inspired by jQuery phylosophy) framework that will allow to:
- 1. get web-applications done faster
- 2. prevent slowing down development in a long term

CMS are good for quick start and frameworks are good in reaching 2nd goal.

So why not ot have framework with togglable CMS features so you can install/uninstall them when you needed?
And even have wide possabilities to override their behavior for match buiseness objectives: redefining and making custom logic on top of them?
- for user/admins: simple to use (full power of CMS gui tools)
- for programmers: much easier and more enjoyable to write new code

This architecture needs to combine features of CMS (like from-a-box solutions) and frameworks (custom functionality).
architecture goals:
- less constarints of architecture
- more clean compact and high-level code in application
- achieve any custom behavior without patching core (that can obstruct updates)
- separate appliactions and core locations (to easier core updating and transferring applications and parts of them)
- security as hight as can to achieve (PDO only and highly carefull with any incoming data: post/get params, url path, user_agent string)

Decision was to separate functionality in atomic blocks: plugins, which can tnteract with each other and core/application via events. Also making them unpluggable so you can install/uninstall them (in 1st implementation they were not truly unpluggable, have this project in production (teach4teach.ru) no bugs happend since release).

I came up with this:
```
index << include('core/main.php'); core::run();
[core] {
  [libs]
  [plugins]
  main << framework entering point
}
[application] {
  [templates] << application templates (layouts) and parts of page (sub-views)
  [plugins] << business logic of application
  css
  js
  img
  ... << custom folders if needeed
  app.controller
}

plugin architecture:
[plugin] {
  [templates] << plugin views
  lang << language constants for multilang support
  css
  js
  ... << custom folders if needeed
  plugin.controller
}
```


# Feature: Plugin inheritance
You can extend any plugin you want from another one and it will inherit controller, views, language constants. You can redefine only what you need for your application.

(later i realised that extending a core-plugin mush behave different from extending an app-plugin)


# Feature: Sub Apps
I wanted to have multiple applications on domain to anyone who has difficulties (as i have) with vhosts and redirects, also for using on shared hosts when you pay more for each sub-domain.


# Future plans:
Development environment:
- plugins managing interface (install, uninstall, turn on/off)
- updater: both core and applications (with url option specifying another instance of framework)
- database management (almost full phpmyadmin functionality but lightweight and more user-friendly)
- project filetree, code editor (like codeanywhere) with autocompletion (ACE / CodeMirror still not choosed) and context API documentation tips

I believe web-development is moving towards cloud-IDE: write code right in browser from any device at any location of the world.

