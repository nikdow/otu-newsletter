var newsletterAdmin = angular.module('newsletterAdmin', []);

newsletterAdmin.controller('newsletterAdminCtrl', ['$scope', '$timeout',
    function( $scope, $timeout ) {
        
        $ = jQuery;
        
        $scope.data = _data;
        $scope.ngdata = _ngdata;
        $scope.main = _main;
        $scope.showLoading = false;

        $scope.ajaxparams = function() {
            var sendmembertype = $.merge( [], $scope.membertype );
               if(sendmembertype.length === $('.membertype').length ) sendmembertype=[]; // prevents query needing to check this
               var sendstate = $.merge( [], $scope.state );
               if(sendstate.length === $('.state').length ) sendstate = [];
               return { 'state':sendstate, 'membertype':sendmembertype, 'clss':$scope.clss };
        };
        
        $scope.isClss = function ( clss ) {
            return $.inArray( clss, $scope.ngdata.clss)>-1;
        }
        $scope.allclss = function ( set ) { // set is boolean, set all or clear all
            if ( set ) {
                $scope.ngdata.clss = [''];
            } else {
                $scope.ngdata.clss = [];
            }
        }
        $scope.toggleclss = function ( clss ) {
            if ( $.inArray ( clss, $scope.ngdata.clss ) > -1 ) {
                var index = $.inArray ( clss, $scope.ngdata.clss );
                if ( index != -1 ) {
                    $scope.ngdata.clss.splice ( index, 1 );
                }
            } else {
                if ( $scope.ngdata.clss[0] == '' ) $scope.ngdata.clss = []; // remove "all" 
                $scope.ngdata.clss.push ( clss );
            }
        }
        $scope.togglemembertype = function(membertype) {
            if($.inArray(membertype, $scope.ngdata.membertype ) > -1 ) {
                var index = $.inArray(membertype, $scope.ngdata.membertype);
                if(index != -1)
                {
                  $scope.ngdata.membertype.splice(index, 1);
                }
            } else {
                $scope.ngdata.membertype.push(membertype);
            }
        };
        $scope.isMemberType = function(membertype) {
            return $.inArray(membertype, $scope.membertype)>-1;
        };
        $scope.togglestate = function(state) {
            if($.inArray(state, $scope.ngdata.state ) > -1 ) {
                var index = $.inArray(state, $scope.ngdata.state );
                if(index != -1) {
                    $scope.ngdata.state.splice(index, 1 );
                }
            } else {
                $scope.ngdata.state.push(state);
            }
        }
        $scope.isState = function(state) {
            return $.inArray(state, $scope.ngdata.state ) > -1;
        }


        $scope.sendNewsletter = function() {
            if ( $('#post input[name=cbdweb_newsletter_test_addresses]').val() === "" ) {
                if ( ! confirm( 'Are you sure?  This will send to all recipients!' ) ) return;
            }
            $('#post input[name=cbdweb_newsletter_send_newsletter]').val('1');
            var data = $('#post').serializeArray();
            data.ngdata = $scope.ngdata;
            $scope.sending = true;
            $scope.showLoading = true;
            $.post( $scope.main.post_url, data, function( response ) {
                $scope.showLoading = false;
                var ajaxdata = $.parseJSON( response );
                $timeout.cancel ( $scope.progress );
                $scope.sending = false;
                $scope.showProgressMessage = true;
                $scope.email = $scope.email || {};
                $scope.showProgressNumber = false;
                $scope.email.message = ajaxdata.success;
                $scope.$apply();
                $('#post input[name=cbdweb_newsletter_send_newsletter]').val('0');
            });
            /* progress */
            $scope.progress = $timeout ( $scope.displayProgress, 1000 );
        };
        
        $scope.displayProgress = function() {
            data = {'action':'cbdweb_newsletter_progress', 'post_id':$('#post input[name=ajax_id]').val() };
            $.post ( $scope.main.ajax_url, data, function ( response ) {
                $scope.showLoading = false;
                $scope.$apply();
                $scope.email = $.parseJSON ( response );
                if($scope.sending) {
                    $scope.showProgressNumber = true;
                    $scope.$apply();
                    $scope.progress = $timeout ( $scope.displayProgress, 1000 );
                }
            });
        };
        
    }
]);