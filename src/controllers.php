<?php

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
    $twig->addGlobal('user', $app['session']->get('user'));

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
        $sql = "SELECT * FROM users WHERE username = '$username' and password = '$password'";
        $user = $app['db']->fetchAssoc($sql);

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

    $paginator = getPaginatorFromTodos($app, $user, $request->get('page', 1));
    $limit = ($paginator['current_page'] - 1) * $paginator['per_page'];

    $sql = "SELECT * FROM todos WHERE user_id = '${user['id']}' LIMIT $limit, {$paginator['per_page']}";
    $todos = $app['db']->fetchAll($sql);

    return $app['twig']->render('todos.html', [
        'todos' => $todos,
        'paginator' => $paginator
    ]);
});


$app->get('/todo/{id}', function ($id) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }

    if ($id){
        $sql = "SELECT * FROM todos WHERE id = '$id'";
        $todo = $app['db']->fetchAssoc($sql);

        return $app['twig']->render('todo.html', [
            'todo' => $todo,
        ]);
    } else {
        $sql = "SELECT * FROM todos WHERE user_id = '${user['id']}'";
        $todos = $app['db']->fetchAll($sql);

        return $app['twig']->render('todos.html', [
            'todos' => $todos,
        ]);
    }
})
->value('id', null);


$app->get('/todo/{id}/json', function ($id) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }

    if ($id && is_numeric($id)){
        $sql = "SELECT * FROM todos WHERE user_id = '${user['id']}' AND id = '$id'";
        $todo = $app['db']->fetchAssoc($sql);

        if($todo) {
            return new JsonResponse($todo);
        }
    }

    return new JsonResponse(['error' => ['message' => 'Record not found']], 404);
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

    $sql = "INSERT INTO todos (user_id, description) VALUES ('$user_id', '$description')";
    $app['db']->executeUpdate($sql);

    $app['session']->getFlashBag()->add('message', 'Task created');

    $paginator = getPaginatorFromTodos($app, $user, $request->get('page', 1));

    return $app->redirect("/todo?page={$paginator['last_page']}");
});

$app->match('/todo/complete/{id}', function (Request $request, $id) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }

    $user_id = $user['id'];

    $sql = "UPDATE todos SET completed = 1 WHERE id  = '$id' AND user_id = '$user_id'";
    $app['db']->executeUpdate($sql);

    return $app->redirect($request->headers->get('referer'));
});

$app->match('/todo/delete/{id}', function ($id) use ($app) {

    $sql = "DELETE FROM todos WHERE id = '$id'";
    $app['db']->executeUpdate($sql);

    $app['session']->getFlashBag()->add('message', 'Task deleted');

    return $app->redirect('/todo');
});
