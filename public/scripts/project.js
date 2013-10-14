var app = angular.module('app', ["ngResource", "ui.bootstrap"]);
app.config(function ($routeProvider, $locationProvider) {
    $routeProvider.
        when('/', {controller: "ListCtrl", templateUrl: 'views/list.html'}).
        // Domain Routes
        when('/domain/add', {controller: "AddDomainCtrl", templateUrl: '/views/form.html'}).
        when('/domain/details/:id', {controller: "DetailsCtrl", templateUrl: '/views/details.html'}).
        when('/domain/edit/:id', {controller: "EditDomainCtrl", templateUrl: '/views/form.html'}).
        when('/domain/delete/:id', {controller: "DeleteDomainCtrl", templateUrl: '/views/delete.html'}).
        // FTP Routes
        when('/ftp/:id', {controller: "EditFTPCtrl", templateUrl: '/views/ftp/form.html'}).
        // Database Routes
        when('/database/:domain/add', {controller: "AddDatabaseCtrl", templateUrl: '/views/database/form.html'}).
        when('/database/:domain/:id/edit', {controller: "EditDatabaseCtrl", templateUrl: '/views/database/form.html'}).
        when('/database/:domain/:id/delete', {controller: "DeleteDatabaseCtrl", templateUrl: '/views/database/delete.html'}).
        // Admin Routes
        when('/other/:domain/add', {controller: "AddDataCtrl", templateUrl: '/views/other/form.html'}).
        when('/other/:domain/:id/edit', {controller: "EditDataCtrl", templateUrl: '/views/other/form.html'}).
        when('/other/:domain/:id/delete', {controller: "DeleteDataCtrl", templateUrl: '/views/other/delete.html'}).

        otherwise({redirectTo: '/'});
    $locationProvider.html5Mode(true);
    $locationProvider.hashPrefix = '!';
});

app.filter('capitalize', function () {
    "use strict";
    return function (input) {
        if (input === undefined) return '';
        return input.charAt(0).toUpperCase() + input.slice(1);
    };
});

app.factory('Search', function () {
    return {text: '', sort: 'name', dir: "ASC", page: 1, limit: 20, next: false};
});

/**
 * Domain Resource
 */
app.factory('Domain', function ($resource) {
    return $resource('/api/domain/:id', {id: '@id'}, {update: {method: 'PUT'}});
});

/**
 * FTP Resource
 */
app.factory('FTP', function ($resource) {
    return $resource('/api/ftp/:id', {id: '@id'}, {update: {method: 'PUT'}});
});

/**
 * Other Data Resource
 */
app.factory('Data', function ($resource) {
    return $resource('/api/data/:domain/:id', {domain: '@domain', id: '@id'}, {update: {method: 'PUT'}});
});

/**
 * Database Resource
 */
app.factory('Database', function ($resource) {
    return $resource('/api/database/:domain/:id', {domain: '@domain', id: '@id'}, {update: {method: 'PUT'}});
});


/**
 * List Domains
 */
app.controller("ListCtrl", function ($scope, $resource, Domain, Search) {
    $scope.search = Search;
    $scope.loadData = function () {
        var offset = ($scope.search.page - 1) * $scope.search.limit;
        Domain.query({offset: offset, limit: $scope.search.limit + 1, search: $scope.search.text, sort: $scope.search.sort, dir: $scope.search.dir}, function (response) {
            if (response.length > $scope.search.limit) {
                // There is another page
                response.pop();
                $scope.search.next = true;
            } else {
                $scope.search.next = false;
            }
            $scope.domains = response;
        });
    };
    $scope.previous = function () {
        if ($scope.search.page > 1) {
            $scope.search.page--;
            $scope.loadData();
        }
    };
    $scope.next = function () {
        if ($scope.search.next) {
            $scope.search.page++;
            $scope.loadData();
        }
    };
    $scope.doSearch = function () {
        $scope.search.next = false;
        $scope.search.page = 1;
        $scope.loadData();
    }
    $scope.loadData();
});

/**
 * Domain Search
 */
app.controller("SearchCtrl", function ($scope, Search, $location) {
    $scope.search = Search;
    $scope.globalSearch = '';
    $scope.searchDomains = function () {
        $scope.search.text = $scope.globalSearch;
        $scope.globalSearch = '';
        $scope.search.page = 1;
        $scope.search.next = false;
        $location.path('/').search({text: $scope.search.text});
    }
});

/**
 * Domain Detail Page
 * Show lists of child data
 */
app.controller("DetailsCtrl", function ($scope, $resource, $routeParams, Domain) {
    $scope.domain = Domain.get({id: $routeParams.id});
});

/**
 * Add domain form
 */
app.controller("AddDomainCtrl", function ($scope, $resource, Domain, $location, $routeParams) {
    $scope.legend = "Add Domain";
    $scope.back_url = "/";
    $scope.domain = new Domain({});
    $scope.resetValidation = function (field) {
        if (field == 'name') {
            $scope.formDomain[field].$setValidity('unique', true);
        }
    };
    $scope.saveDomain = function () {
        if ($scope.formDomain.$valid) {
            $scope.domain.$save({}, function (added_domain) {
                $scope.domain = added_domain;
                $location.path('/domain/details/' + added_domain.id).replace();
            }, function (response) {
                if (response.status == 400) {
                    angular.forEach(response.data.errors, function (errors, name) {
                        angular.forEach(errors, function (message, type) {
                            $scope.formDomain[name].$setValidity(type, false);
                        });
                    });
                }
            });
        }
    };
});

/**
 * Update domain form
 */
app.controller("EditDomainCtrl", function ($scope, $resource, $routeParams, Domain, $location) {
    $scope.legend = "Edit Domain";
    $scope.back_url = "/domain/details/" + $routeParams.id;
    $scope.domain = Domain.get({id: $routeParams.id});
    $scope.resetValidation = function (field) {
        if (field == 'name') {
            $scope.formDomain[field].$setValidity('unique', true);
        }
    };
    $scope.saveDomain = function () {
        if ($scope.formDomain.$valid) {
            $scope.domain.$update({domain_id: $routeParams.domain_id}, function (updated_domain) {
                $scope.domain = updated_domain;
                $location.path('/domain/details/' + updated_domain.id).replace();
            }, function (response) {
                if (response.status == 400) {
                    angular.forEach(response.data.errors, function (errors, name) {
                        angular.forEach(errors, function (message, type) {
                            $scope.formDomain[name].$setValidity(type, false);
                        });
                    });
                }
            });
        }
    };
});

/**
 * Delete a domain
 */
app.controller("DeleteDomainCtrl", function ($scope, $resource, $routeParams, Domain, $location) {
    $scope.domain = Domain.get({id: $routeParams.id});
    $scope.deleteDomain = function () {
        $scope.domain.$delete(function () {
            $location.path('/').replace();
        });
    };
});

/**
 * Update FTP credentials form
 */
app.controller("EditFTPCtrl", function ($scope, $resource, $routeParams, Domain, FTP, $location) {
    $scope.legend = "Update FTP Credentials";
    $scope.back_url = "/domain/details/" + $routeParams.domain;
    $scope.domain = Domain.get({id: $routeParams.id});
    $scope.ftp = FTP.get({id: $routeParams.id});
    $scope.saveFTP = function () {
        if ($scope.formFTP.$valid) {
            $scope.ftp.$update(function (updated_record) {
                $scope.ftp = updated_record;
                $location.path('/domain/details/' + updated_record.id).replace();
            }, function (response) {
                if (response.status == 400) {
                    angular.forEach(response.data.errors, function (errors, name) {
                        angular.forEach(errors, function (message, type) {
                            $scope.formFTP[name].$setValidity(type, false);
                        });
                    });
                }
            });
        }
    };
});

/**
 * Add Database login form
 */
app.controller("AddDatabaseCtrl", function ($scope, $resource, $routeParams, Domain, Database, $location) {
    $scope.legend = "Add Database Credentials";
    $scope.back_url = "/domain/" + $routeParams.domain;
    $scope.domain = Domain.get({id: $routeParams.domain});
    $scope.database = new Database({domain: $routeParams.domain, dbtype: 'MYSQL'});
    $scope.types = [
        {value: 'MYSQL', label: 'MySQL'},
        {value: 'SQLITE', label: 'SQLite'},
        {value: 'MSSQL', label: 'MSSQL'},
        {value: 'ORACLE', label: 'Oracle Database'},
        {value: 'PGSQL', label: 'Postgres'},
        {value: 'ACCESS', label: 'Microsoft Access'},
        {value: 'OTHER', label: 'Other'}
    ];
    $scope.saveDatabase = function () {
        if ($scope.formDatabase.$valid) {
            $scope.database.$save({}, function (added_record) {
                $scope.database = added_record;
                $location.path('/domain/details/' + added_record.domain).replace();
            }, function (response) {
                if (response.status == 400) {
                    angular.forEach(response.data.errors, function (errors, name) {
                        angular.forEach(errors, function (message, type) {
                            $scope.formDatabase[name].$setValidity(type, false);
                        });
                    });
                }
            });
        }
    };
});

/**
 * Update database login form
 */
app.controller("EditDatabaseCtrl", function ($scope, $resource, $routeParams, Domain, Database, $location) {
    $scope.legend = "Update Database Login Credentials";
    $scope.back_url = "/domain/details/" + $routeParams.domain;
    $scope.domain = Domain.get({id: $routeParams.domain});
    $scope.database = Database.get({id: $routeParams.id, domain: $routeParams.domain});
    $scope.types = [
        {value: 'MYSQL', label: 'MySQL'},
        {value: 'SQLITE', label: 'SQLite'},
        {value: 'MSSQL', label: 'MSSQL'},
        {value: 'ORACLE', label: 'Oracle Database'},
        {value: 'PGSQL', label: 'Postgres'},
        {value: 'ACCESS', label: 'Microsoft Access'},
        {value: 'OTHER', label: 'Other'}
    ];
    $scope.saveDatabase = function () {
        if ($scope.formDatabase.$valid) {
            $scope.database.$update(function (updated_record) {
                $scope.database = updated_record;
                $location.path('/domain/details/' + updated_record.domain).replace();
            }, function (response) {
                if (response.status == 400) {
                    angular.forEach(response.data.errors, function (errors, name) {
                        angular.forEach(errors, function (message, type) {
                            $scope.formDatabase[name].$setValidity(type, false);
                        });
                    });
                }
            });
        }
    };
});

/**
 * Delete database login
 */
app.controller("DeleteDatabaseCtrl", function ($scope, $resource, $routeParams, Domain, Database, $location) {
    $scope.domain = Domain.get({id: $routeParams.domain});
    $scope.database = Database.get({id: $routeParams.id, domain: $routeParams.domain});
    $scope.deleteDatabase = function () {
        $scope.database.$delete(function () {
            $location.path('/domain/details/' + $routeParams.domain).replace();
        });
    };
});

/**
 * Add other data
 */
app.controller("AddDataCtrl", function ($scope, $resource, $routeParams, Domain, Data, $location, $http) {
    $scope.legend = "Add Other Data";
    $scope.back_url = "/domain/" + $routeParams.domain;
    $scope.domain = Domain.get({id: $routeParams.domain});
    $http({method: 'GET', url: '/api/data/' + $routeParams.domain + '/group'}).success(function (data) {
        $scope.groups = data;
    });
    $scope.data = new Data({domain: $routeParams.domain});
    $scope.resetValidation = function (field) {
        if (field == 'name') {
            $scope.formData[field].$setValidity('unique', true);
        }
    };
    $scope.saveData = function () {
        if ($scope.formData.$valid) {
            $scope.data.$save(function (added_record) {
                $scope.data = added_record;
                $location.path('/domain/details/' + added_record.domain).replace();
            }, function (response) {
                if (response.status == 400) {
                    angular.forEach(response.data.errors, function (errors, name) {
                        angular.forEach(errors, function (message, type) {
                            $scope.formData[name].$setValidity(type, false);
                        });
                    });
                }
            });
        }
    };
});

/**
 * Update other data
 */
app.controller("EditDataCtrl", function ($scope, $resource, $routeParams, Domain, Data, $location, $http) {
    $scope.legend = "Update Data Row";
    $scope.back_url = "/domain/details/" + $routeParams.domain;
    $scope.domain = Domain.get({id: $routeParams.domain});
    $scope.data = Data.get({id: $routeParams.id, domain: $routeParams.domain});
    $scope.resetValidation = function (field) {
        if (field == 'name') {
            $scope.formData[field].$setValidity('unique', true);
        }
    };
    $http({method: 'GET', url: '/api/data/' + $routeParams.domain + '/group'}).success(function (data) {
        $scope.groups = data;
    });
    $scope.saveData = function () {
        if ($scope.formData.$valid) {
            $scope.data.$update(function (updated_record) {
                $scope.data = updated_record;
                $location.path('/domain/details/' + updated_record.domain).replace();
            }, function (response) {
                if (response.status == 400) {
                    angular.forEach(response.data.errors, function (errors, name) {
                        angular.forEach(errors, function (message, type) {
                            $scope.formData[name].$setValidity(type, false);
                        });
                    });
                }
            });
        }
    };
});

/**
 * Delete other data
 */
app.controller("DeleteDataCtrl", function ($scope, $resource, $routeParams, Domain, Data, $location) {
    $scope.domain = Domain.get({id: $routeParams.domain});
    $scope.data = Data.get({id: $routeParams.id, domain: $routeParams.domain});
    $scope.deleteData = function () {
        $scope.data.$delete(function () {
            $location.path('/domain/details/' + $routeParams.domain).replace();
        });
    };
});
