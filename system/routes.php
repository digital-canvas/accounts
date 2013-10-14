<?php

$app->notFound('Api\\Controller\\Error::notfoundAction');

$app->group('/api', function () use ($app) {

    // List Actions
    $app->get('/', 'Api\\Controller\\Index::listAction');

    //////////////////////
    // Domain Methods  //
    //////////////////////
    $app->group('/domain', function () use ($app) {

        // Count Domains
        $app->get('/count', 'Api\\Controller\\Domain::countAction');

        // List Domains
        $app->get('/', 'Api\\Controller\\Domain::listAction');

        // Get Domain
        $app->get('/:id', 'Api\\Controller\\Domain::detailsAction');

        // Create a new Domain
        $app->post('/', 'Api\\Controller\\Domain::addAction');

        // Update an individual Domain
        $app->put('/:id', 'Api\\Controller\\Domain::updateAction');

        // Delete an individual Domain
        $app->delete('/:id', 'Api\\Controller\\Domain::deleteAction');

    });

    //////////////////////
    // FTP Methods      //
    //////////////////////
    $app->group('/ftp', function () use ($app) {
            // Get FTP
        $app->get('/:domain_id', 'Api\\Controller\\FTP::detailsAction');

        // Update FTP
        $app->put('/:domain_id', 'Api\\Controller\\FTP::updateAction');
    });


    //////////////////////
    // Database Methods //
    //////////////////////
    $app->group('/database', function () use ($app) {
        // Get Database
        $app->get('/:domain_id/:id', 'Api\\Controller\\Database::detailsAction');

        // Add Database
        $app->post('/:domain_id', 'Api\\Controller\\Database::addAction');

        // Update Database
        $app->put('/:domain_id/:id', 'Api\\Controller\\Database::updateAction');

        // Delete Database
        $app->delete('/:domain_id/:id', 'Api\\Controller\\Database::deleteAction');
    });

    ////////////////////////
    // Other Data Methods //
    ////////////////////////
    $app->group('/data', function () use ($app) {
        // Get Groups
        $app->get('/:domain_id/group', 'Api\\Controller\\Data::listGroupsAction');

        // Add Data Row
        $app->post('/:domain_id', 'Api\\Controller\\Data::addAction');

        // Gets Data Row Details
        $app->get('/:domain_id/:id', 'Api\\Controller\\Data::detailsAction');

        // Update Data Row
        $app->put('/:domain_id/:id', 'Api\\Controller\\Data::updateAction');

        // Delete Data Row
        $app->delete('/:domain_id/:id', 'Api\\Controller\\Data::deleteAction');
    });



});