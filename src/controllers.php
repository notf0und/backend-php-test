<?php

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

function getPaginatorFromTodos($app, $user, $page) {
    $perPage = 10;
    $sql = "SELECT count(*) as total FROM todos WHERE user_id = '${user['id']}'";
    $total = $app['db']->fetchOne($sql);
    $lastPage = ceil($total/$perPage);

    $lastPage = ceil($total/$perPage);
    $limit = ($page - 1) * $perPage;

    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $page,
        'last_page' => $lastPage,
    ];
}

function jsonResponseError($code) {
    $messages = [
        401 => 'Unauthenticated',
        404 => 'Record not found',
        422 => 'Validation error',
        'default' => 'An error happened!'
    ];

    if(!array_key_exists($code, $messages)) {
        $code = 'default';
    }

    return new JsonResponse(['error' => ['message' => $messages[$code]]], 404);
}

$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
    $twig->addGlobal('user', $app['session']->get('user'));
    $twig->addExtension(new \Twig\ViteAssetExtension(
        $app['config']['vite']['dev'],
        realpath(dirname(__FILE__)) . '/../web/assets/manifest.json'));
    return $twig;
}));

$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html', [
        'readme' => file_get_contents('README.md'),
    ]);
});


$app->match('/login', function (Request $request) use ($app) {
    $username = $request->get('username');
    $password = $request->get('password');

    if ($username) {
        $sql = 'SELECT * FROM users WHERE username = :username AND password = :password';

        $query = $app['db']->prepare($sql);
        $query->execute(compact('username', 'password'));
        $user = $query->fetchAssociative();

        if ($user){
            $app['session']->set('user', $user);
            return $app->redirect('/todo');
        }
    }

    return $app['twig']->render('login.html', array());
});


$app->get('/logout', function () use ($app) {
    $app['session']->set('user', null);
    return $app->redirect('/');
});


$app->get('/todo', function (Request $request) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }

    // Functionality here has been removed since now
    // react fetch the data using /todo/json endpoint

    return $app['twig']->render('todos.html');
});

$app->get('/todo/json', function (Request $request) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }

    $sql = "SELECT * FROM todos WHERE user_id = '${user['id']}'";
    $todos = $app['db']->fetchAll($sql);
    return new JsonResponse($todos);
});

$app->get('/todo/{id}', function ($id) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }

    if ($id){
        $sql = 'SELECT * FROM todos WHERE id = :id';

        $query = $app['db']->prepare($sql);
        $query->execute(compact('id'));
        $todo = $query->fetchAssociative();

        if($todo) {
            return $app['twig']->render('todo.html', ['todo' => $todo,]);
        }

        return $app->redirect('/todo');    }
})
->value('id', null);


$app->get('/todo/{id}/json', function ($id) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return jsonResponseError(401);
    }

    if ($id && is_numeric($id)){
        $user_id = $user['id'];
        $sql = 'SELECT * FROM todos WHERE id = :id AND user_id = :user_id';

        $query = $app['db']->prepare($sql);
        $query->execute(compact('id', 'user_id'));
        $todo = $query->fetchAssociative();

        if($todo) {
            return new JsonResponse($todo);
        }
    }

    return jsonResponseError(404);
})
->value('id', null);


$app->post('/todo/add', function (Request $request) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }

    $user_id = $user['id'];
    $description = $request->get('description');

    if(!$description) {
        $app['session']->getFlashBag()->add('validation', 'Description field is required');
        return $app->redirect($request->headers->get('referer'));
    }

    $sql = 'INSERT INTO todos (user_id, description) VALUES (:user_id, :description)';
    $query = $app['db']->prepare($sql);
    $query->execute(compact( 'user_id', 'description'));

    $app['session']->getFlashBag()->add('message', 'Task created');
    $paginator = getPaginatorFromTodos($app, $user, $request->get('page', 1));

    return $app->redirect("/todo?page={$paginator['last_page']}");
});

$app->post('/todo/add/json', function (Request $request) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return jsonResponseError(401);
    }

    $data = json_decode($request->getContent(), true);
    $hasDescription = array_key_exists('description', $data);
    if (($hasDescription && empty($data['description'])) || !$hasDescription) {
        return jsonResponseError(422);
    }

    $user_id = $user['id'];
    $description = $data['description'];

    $sql = 'INSERT INTO todos (user_id, description) VALUES (:user_id, :description)';
    $query = $app['db']->prepare($sql);
    $query->execute(compact( 'user_id', 'description'));

    $id = $app['db']->lastInsertId();

    $sql = "SELECT * FROM todos WHERE id  = '$id' AND user_id = '$user_id'";
    $record = $app['db']->fetchAssoc($sql);

    return JsonResponse::create($record);
});

$app->match('/todo/complete/{id}', function (Request $request, $id) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }

    $user_id = $user['id'];
    $sql = 'UPDATE todos SET completed = 1 WHERE id  = :id AND user_id = :user_id';
    $query = $app['db']->prepare($sql);
    $query->execute(compact( 'id', 'user_id'));

    return $app->redirect($request->headers->get('referer'));
});

$app->match('/todo/complete/{id}/json', function (Request $request, $id) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return jsonResponseError(401);
    }

    $user_id = $user['id'];
    $sql = 'UPDATE todos SET completed = 1 WHERE id  = :id AND user_id = :user_id';
    $query = $app['db']->prepare($sql);
    $query->execute(compact( 'id', 'user_id'));

    return JsonResponse::create(true);
});

$app->match('/todo/delete/{id}', function ($id) use ($app) {

    $sql = "DELETE FROM todos WHERE id = :id";
    $query = $app['db']->prepare($sql);
    $query->execute(compact( 'id'));

    $app['session']->getFlashBag()->add('message', 'Task deleted');

    return $app->redirect('/todo');
});


$app->match('/todo/delete/{id}/json', function ($id) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return jsonResponseError(401);
    }

    $sql = "DELETE FROM todos WHERE id = :id";
    $query = $app['db']->prepare($sql);
    $query->execute(compact( 'id'));

    return JsonResponse::create(true);
});
