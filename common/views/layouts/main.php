<?php

/** @var yii\web\View $this */
/** @var string $content */

use common\assets\AppAsset;
use common\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;
use yii\web\View;

AppAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerMetaTag(['name' => 'description', 'content' => $this->params['meta_description'] ?? '']);
$this->registerMetaTag(['name' => 'keywords', 'content' => $this->params['meta_keywords'] ?? '']);
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/x-icon', 'href' => Yii::getAlias('@web/favicon.ico')]);

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" data-theme="material" data-theme-colors="default" data-layout="semibox" data-preloader="disable" data-sidebar-visibility="<?= Yii::$app->user->can('expert') ? 'show' : 'hidden' ?>" data-layout-width="fluid" data-layout-position="fixed" data-topbar="light" data-layout-style="default" data-sidebar="dark" data-sidebar-size="lg">

<head>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>

<body>
    <?php $this->beginBody() ?>

    <header id="page-topbar">
        <div class="layout-width">
            <div class="navbar-header">
                <div class="d-flex">
                    
                    <!-- LOGO -->
                    <div class="ps-3 <?= Yii::$app->user->can('expert') ? 'd-md-none' : ''; ?>">
                        <?= Html::a('<span class="logo-sm">D</span><span class="logo-lg">Demo</span>', ['/'], ['class' => 'logo logo-dark fs-3'])?>

                        <?= Html::a('<span class="logo-sm">D</span><span class="logo-lg">Demo</span>', ['/'], ['class' => 'logo logo-light fs-3'])?>
                    </div>

                    <?php if (Yii::$app->user->can('expert')): ?>
                    <button type="button"
                        class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger material-shadow-none"
                        id="topnav-hamburger-icon">
                        <span class="hamburger-icon">
                            <span></span>
                            <span></span>
                            <span></span>
                        </span>
                    </button>
                    <?php endif; ?>
                </div>

                <div class="d-flex align-items-center">

                    <div class="ms-1 header-item d-none d-sm-flex">
                        <button type="button"
                            class="btn btn-icon btn-topbar material-shadow-none btn-ghost-secondary rounded-circle light-dark-mode">
                            <i class="bx bx-moon fs-22"></i>
                        </button>
                    </div>

                    <?php if (!Yii::$app->user->isGuest): ?>
                    <div class="dropdown ms-sm-3 header-item topbar-user">
                        <button type="button" class="btn material-shadow-none" id="page-header-user-dropdown"
                            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="d-flex align-items-center">
                                <?= Html::img(Yii::getAlias('@web/images/users/user-dummy-img.jpg'), ['class' => 'rounded-circle header-profile-user', 'alt' => 'Avatar'])?>
                                <span class="text-start ms-xl-2">
                                    <span class="d-inline-block ms-1 fw-medium user-name-text"><?= Yii::$app->user->identity->login ?></span>
                                </span>
                            </span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <?= Html::a('<i class="mdi mdi-logout text-muted fs-16 align-middle me-1"></i><span class="align-middle" data-key="t-logout">Выход</span>', ['logout'], ['class' => 'dropdown-item', 'data' => ['method' => 'post']]) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <?php if (Yii::$app->user->can('expert')): ?>
    <div class="app-menu navbar-menu pt-0">
        <div class="navbar-brand-box position-relative top-0 rounded d-block">
            <?= Html::a('<span class="logo-sm">D</span><span class="logo-lg">Demo</span>', ['/'], ['class' => 'logo logo-dark fs-3'])?>

            <?= Html::a('<span class="logo-sm">D</span><span class="logo-lg">Demo</span>', ['/'], ['class' => 'logo logo-light fs-3'])?>
        </div>

        <div id="scrollbar" class="pt-1">
            <div class="container-fluid">
                <div id="two-column-menu">
                </div>
                <?=
                    Nav::widget([
                        'route' => Yii::$app->request->getPathInfo(),
                        'activateItems' => true,
                        'activateParents' => true,
                        'options' => [
                            'id' => 'navbar-nav',
                            'class' => 'navbar-nav'
                        ],
                        'items' => [
                            '<li class="menu-title"><i class="ri-more-fill"></i><span data-key="t-menu">Меню</span></li>',
                            [
                                'label' => '<i class="ri-user-settings-line"></i><span data-key="t-experts">Эксперты</span>', 
                                'encode' => false, 
                                'url' => ['/experts'], 
                                'linkOptions' => [
                                    'class' => 'nav-link menu-link', 
                                    'data-key' => 't-experts'
                                ],
                                'active' => (Yii::$app->request->getPathInfo() == '' || Yii::$app->request->getPathInfo() == 'experts')
                            ],
                            [
                                'label' => '<i class="ri-function-add-line"></i><span data-key="t-events">Чемпионаты</span>', 
                                'encode' => false, 
                                'url' => ['/events'], 
                                'linkOptions' => [
                                    'class' => 'nav-link menu-link', 'data-key' => 't-events'
                                ]
                            ],
                            [
                                'label' => '<i class="ri-user-add-line"></i><span data-key="t-students">Студенты</span>', 
                                'encode' => false, 
                                'url' => ['/students'], 
                                'linkOptions' => [
                                    'class' => 'nav-link menu-link', 'data-key' => 't-students'
                                ]
                            ],
                            [
                                'label' => '<i class="ri-equalizer-line"></i><span data-key="t-modules">Модули</span>', 
                                'encode' => false, 
                                'url' => ['/modules'], 
                                'linkOptions' => [
                                    'class' => 'nav-link menu-link', 'data-key' => 't-modules'
                                ]
                            ],
                            [
                                'label' => '<i class="ri-upload-2-line"></i><span data-key="t-files">Файлы</span>', 
                                'encode' => false, 
                                'url' => ['/files'], 
                                'linkOptions' => [
                                    'class' => 'nav-link menu-link', 'data-key' => 't-files'
                                ]
                            ],
                            '<li class="nav-item">
                                <a class="nav-link menu-link" href="#sidebarDashboards" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarDashboards">
                                    <i class="ri-dashboard-2-line"></i> <span data-key="t-dashboards">Студенты</span>
                                </a>
                                <div class="collapse menu-dropdown" id="sidebarDashboards">
                                    <ul class="nav nav-sm flex-column">
                                        <li class="nav-item">
                                            <a href="dashboard-analytics.html" class="nav-link" data-key="t-analytics"> Event 1 </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="dashboard-crm.html" class="nav-link" data-key="t-crm"> Event 2 </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="index.html" class="nav-link" data-key="t-ecommerce"> Event 3 </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="dashboard-crypto.html" class="nav-link" data-key="t-crypto"> Event 4 </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="dashboard-projects.html" class="nav-link" data-key="t-projects"> Event 5 </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>'
                        ],
                    ]);
                ?>
            </div>
        </div>

        <div class="sidebar-background"></div>
    </div>
    <?php else: ?>
        <div id="navbar-nav"></div>
        <div id="scrollbar"></div>
    <?php endif; ?>

    <!-- <div class="app-menu navbar-menu pt-0">
        <div id="scrollbar">
                <div id="navbar-nav">
                    <div class="my-cont"></div>
                </div>
        </div>
    </div> -->

    <div class="vertical-overlay"></div>
    
    <main class="main-content">
        <div class="page-content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0"><?= Html::encode($this->title); ?></h4>

                            <?= Breadcrumbs::widget([
                                'options' => [
                                    'class' => 'm-0'
                                ],
                                'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                            ]) ?>

                        </div>
                    </div>
                </div>
                <?= $content ?>
            </div>
            <!-- container-fluid -->
        </div>
        <!-- End Page-content -->

        <footer class="footer">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6">
                        <script>
                            document.write(new Date().getFullYear());
                        </script>
                        © JavaLetS.
                    </div>
                </div>
            </div>
        </footer>
    </main>

    <button class="btn btn-danger btn-icon" id="back-to-top">
        <i class="ri-arrow-up-line"></i>
    </button>

    <div id="preloader">
        <div id="status">
            <div class="spinner-border text-primary avatar-sm" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>

    <?php $this->endBody() ?>
</body>

</html>
<?php $this->endPage() ?>