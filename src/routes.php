<?php
// Routes

/**
 * Homepage controller
 */
$app->get('/', function ($request, $response, $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->view->render($response, 'index.twig', $args);
})->setName('homepage');


/**
 * Post Listing Controller
 */
$app->get('/{type:latest|popular|random}/', function ($request, $response, $args) {
    $this->logger->info("'/post/".$args['type']."/' route");
    
    $article = new Models\Article;
    $tag = new Models\Tags;
    $data['type'] = $args['type'];

    if ($data['type'] === 'random') {
        $randomArticle = $article->getRandomArticle();
        $id = $randomArticle['id'];
        $slug = $article->slugify($randomArticle['title']);
        $redirectPath = $this->router->pathFor('article', ['id' => $id]) . $slug . '/';
        return $response->withRedirect((string)$redirectPath, 302);
    }
    $data['title'] = ($args['type'] === 'latest') ? 'Latest post' : 'Popular post';
    $data['postlist'] = $article->getItem($args['type']);

    foreach ($data['postlist'] as $key => $value) {
        $data['postlist'][$key]['slug'] = $article->slugify($value['title']);
        $data['postlist'][$key]['tags'] = $tag->getTagsByArticleId($value['id']);
    }

    return $this->view->render($response, 'post-list.twig', $data);
});

$app->get('/post/[{id:[0-9]+}/[{slug}/]]', function ($request, $response, $args) {
    $this->logger->info("'/post' route");

    // Initiate article model
    $article = new Models\Article;
    $tag = new Models\Tags;
    $post = $article->getItemById($args['id']);
    if (empty($post)) {
        return $this->view->render($response, 'notice.twig', $article->notFound());
    }
    $post['tags'] = $tag->getTagsByArticleId($post['id']);
    $slug = $article->slugify($post['title']);

    // Redirect article post if slug is wrong or not exists
    if (!isset($args['slug']) || $args['slug'] !== $slug) {
        $uri = $request->getUri();
        $path = $uri->getPath();
        $redirectPath = $this->router->pathFor('article', ['id' => $args['id']]) . $slug .'/';
        return $response->withRedirect((string)$redirectPath, 302);
    }

    // Update article view counter
    $article->updateViewCount($post['id']);

    // Data to be passed to view renderer
    $data = [
        'title' => 'Test Post',
        'post' => $post,
        'id' => $args['id'],
        'args' => $args,
    ];
    return $this->view->render($response, 'post.twig', $data);
})->setName('article');

$app->get('/tags/[{tag_name}/]', function ($request, $response, $args) {
    $this->logger->info("'/tags' route");
    $tag = new Models\Tags;
    $article = new Models\Article;
    $data = [];
    $data['title'] = 'Categories';

    // Render into post list
    if (isset($args['tag_name'])) {
        $data['title'] = ucfirst($args['tag_name']);
        $data['flash'] = 'Post with tag "' . $args['tag_name'] . '"';
        $data['postlist'] = $article->getItemByTagName($args['tag_name']);
        foreach ($data['postlist'] as $key => $value) {
            $data['postlist'][$key]['tags'] = $tag->getTagsByArticleId($value['id']);
        }
        return $this->view->render($response, 'post-list.twig', $data);
    }
    $data['tags'] = $tag->getAllTags();
    return $this->view->render($response, 'tags-list.twig', $data);
});

$app->get('/test/', function ($request, $response, $args) {
    return $this->view->render($response, 'test-form.twig');
});
$app->post('/test/', function ($request, $response, $args) {
    $data['post'] = $request->getParsedBody();
    return $this->view->render($response, 'test-form.twig', $data);
});
