// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Module to display and manage reactions and difficulty tracks on course page.
 * @copyright  2020 Quentin Fombaron, 2021 Astor Bizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/notification'], function($, ajax, notification) {

    /**
     * Call a function each time a course section is loaded.
     * @param {Function} call Function to call.
     */
    function callOnModulesListLoad(call) {
        call();

        // The following listener is needed for the Tiles course format, where sections are loaded on demand.
        if (document.body.classList.contains('format-tiles')) {
            MutationObserver = window.MutationObserver || window.WebKitMutationObserver;
            (new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    mutation.addedNodes.forEach(function(addedNode) {
                        if (addedNode.nodeType === Node.TEXT_NODE) {
                            return;
                        }
                        if ((addedNode.id || '').startsWith('module-')) {
                            call(addedNode.parentElement);
                        } else if (addedNode.querySelector(('[id^=module-]'))) {
                            call(addedNode);
                        }
                    });
                });
            })).observe(document.querySelector('.course-content'), {
                childList: true,
                subtree: true,
            });
        }
    }

    /**
     * Set up difficulty tracks on course modules.
     * @param {Element} root DOM element in which to restrict the search for modules.
     * @param {Array} difficultyLevels Array of difficulty tracks, one entry for each course module.
     * @param {Array} trackColors Tracks colors, from block plugin configuration.
     * @param {Number|null} cmid The course module ID, if this page is a module view.
     */
    function setUpDifficultyTracks(root, difficultyLevels, trackColors, cmid) {
        difficultyLevels.forEach(function(module) {
            var moduleRoot = root.querySelector('#module-' + module.id);
            if (!moduleRoot && module.cmid !== cmid) { // The module is not present there.
                return;
            }
            var difficultyLevel = parseInt(module.difficultyLevel);
            var title = '';
            if (difficultyLevel > 0) {
                var tracklabel = ['greentrack', 'bluetrack', 'redtrack', 'blacktrack'][difficultyLevel - 1];
                title = M.util.get_string(tracklabel, 'block_point_view');
            }
            var track = document.createElement('div');
            track.title = title;
            track.classList.add('block_point_view', 'track');
            track.style.backgroundColor = trackColors[difficultyLevel];
            // Decide where to put the track.
            var container = moduleRoot.querySelector('.activitytitle');
            if (container && container.closest('.activity-grid')) {
                // Moodle 4.3+.
                container = container.closest('.activity-grid');
            }
            if (!container) {
                // This seems to be a label.
                container = moduleRoot.querySelector('.activity-item .description, .activity-item .activity-altcontent');
            }

            // Add the track.
            if (!container.querySelector('.block_point_view.track')) {
                // If there is indentation, move the track after it.
                if (container.querySelector('.mod-indent')) {
                    container.insertBefore(track, container.querySelector('.mod-indent').nextSibling);
                } else {
                    container.prepend(track);
                }
            }

            if (cmid === module.id) {
                // This is a module page, add the track to the title.
                document.querySelector('.page-context-header').prepend(track);
            }
        });
    }

    /**
     * Get a jQuery object in reaction zone for given module ID.
     * @param {Number} moduleId Module ID.
     * @param {String} selector (optional) Sub-selector.
     * @return {jQuery} If selector was provided, the corresponding jQuery object within the reaction zone.
     *  If not, the reaction zone jQuery object.
     */
    function $get(moduleId, selector) {
        var $element = $('#module-' + moduleId + ' .block_point_view.reactions-container');
        if (typeof (selector) === 'undefined') {
            return $element;
        } else {
            return $element.find(selector);
        }
    }

    // Enumeration of the possible reactions.
    var Reactions = {
            none: 0,
            easy: 1,
            better: 2,
            hard: 3
    };

    // Array of Reaction of the user for the activity.
    var reactionVotedArray = {};

    /**
     * Set up difficulty tracks on course modules.
     * @param {Element} root DOM element in which to restrict the search for modules.
     * @param {Number} courseId Course ID.
     * @param {Array} modulesWithReactions Array of reactions state, one entry for each course module with reactions enabled.
     * @param {String} reactionsHtml HTML fragment for reactions.
     * @param {Array} pixSrc Array of pictures sources for group images.
     * @param {Number|null} cmid The course module ID, if this page is a module view.
     */
    function setUpReactions(root, courseId, modulesWithReactions, reactionsHtml, pixSrc, cmid) {
        // For each selected module, create a reaction zone.
        modulesWithReactions.forEach(function(module) {
            var moduleId = parseInt(module.cmid);
            var moduleRoot = root.querySelector('#module-' + moduleId);
            if (!moduleRoot && module.cmid !== cmid) { // The module is not present there.
                return;
            }

            var uservote = parseInt(module.uservote);

            // Initialise reactionVotedArray.
            reactionVotedArray[moduleId] = uservote;

            if (module.cmid === cmid) {
                // Simulate an activity row on course page (so we treat it the same for what's next).
                $('<div id="module-' + moduleId + '" class="activity-wrapper mr-5" style="width: 165px;">')
                .insertAfter($('.header-actions-container'))
                .prepend('<div class="activity-instance">');
            }

            if ($get(moduleId).length === 0) {

                // Add the reaction zone to the module.
                var $module = $(moduleRoot);
                if ($module.is('.modtype_label')) {
                    // Label.
                    if ($module.find('.activity-grid').is('.noname-grid') && $module.find('.activity-altcontent').length) {
                        $module.find('.activity-altcontent').css('display', 'flex').append(reactionsHtml);
                    } else {
                        $module.find('.description, .activity-grid').first().before(reactionsHtml);
                    }
                } else if ($module.find('.activity-name-area').length) {
                    // Moodle 4.5+.
                    var $reactions = $(reactionsHtml);
                    if (!$module.find('.activity-groupmode-info, .activity-completion').length) {
                        $reactions.addClass('lastofline');
                    }
                    $module.find('.activity-name-area').append($reactions);
                } else if ($module.find('.tiles-activity-container').length) {
                    // Tiles format.
                    $module.find('.tiles-activity-container').after(reactionsHtml);
                } else {
                    $module.find('.activity-instance').after(reactionsHtml);
                }

                // Setup reaction change.
                var reactionsLock = false;
                $get(moduleId, '.reaction img')
                .click(function() {
                    // Use a mutex to avoid query / display inconsistencies.
                    // This is not a perfect mutex, but is actually enough for our needs.
                    if (reactionsLock === false) {
                        reactionsLock = true;
                        reactionChange(courseId, moduleId, $(this).data('reactionname'))
                        .always(function() {
                            reactionsLock = false;
                            updateGroupImgAndNb(moduleId, pixSrc);
                        });
                    }
                });

                // Initialize reactions state.
                $get(moduleId, '.reactionnb').each(function() {
                    var reactionName = $(this).data('reactionname');
                    var reactionNb = parseInt(module['total' + reactionName]);
                    updateReactionNb(moduleId, reactionName, reactionNb, uservote === Reactions[reactionName]);
                });
                updateGroupImgAndNb(moduleId, pixSrc);

                // Setup animations.
                setupReactionsAnimation(moduleId, pixSrc);
            }
        });
    }

    /**
     * Manage a reaction change (user added, removed or updated their vote).
     * @param {Number} courseId Course ID.
     * @param {Number} moduleId Module ID.
     * @param {String} reactionName The reaction being clicked.
     * @returns {Promise} A promise, result of the change operations (ajax call and UI update).
     */
    function reactionChange(courseId, moduleId, reactionName) {

        var reactionSelect = Reactions[reactionName];
        var previousReaction = reactionVotedArray[moduleId];

        // If the reaction being clicked is the current one, it is a vote remove.
        var newVote = (reactionSelect === previousReaction) ? Reactions.none : reactionSelect;

        return ajax.call([
            {
                methodname: 'block_point_view_update_db',
                args: {
                    func: 'update',
                    courseid: courseId,
                    cmid: moduleId,
                    vote: newVote
                }
            }
        ])[0]
        .done(function() {
            reactionVotedArray[moduleId] = newVote; // Set current reaction.
            if (previousReaction !== Reactions.none) {
                // User canceled their vote (or updated to another one).
                var previousReactionName = ['', 'easy', 'better', 'hard'][previousReaction];
                updateReactionNb(moduleId, previousReactionName, -1, false);
            }
            if (newVote !== Reactions.none) {
                // User added or updated their vote.
                updateReactionNb(moduleId, reactionName, +1, true); // Add new vote.
            }
        })
        .fail(notification.exception);
    }

    /**
     * Update the reactions group image and total number according to current votes.
     * @param {Number} moduleId Module ID.
     * @param {Array} pix Array of pictures sources for group images.
     */
    function updateGroupImgAndNb(moduleId, pix) {
        // Build group image name.
        var groupImg = 'group_';
        var totalNb = 0;
        $get(moduleId, '.reactionnb').each(function() {
            var reactionNb = parseInt($(this).text());
            if (reactionNb > 0) {
                groupImg += $(this).data('reactionname').toUpperCase().charAt(0); // Add E, B or H.
            }
            totalNb += reactionNb;
        });
        // Modify the image source of the reaction group.
        $get(moduleId, '.group_img').attr('src', pix[groupImg]);

        // Update the total number of votes.
        var $groupNbWrapper = $get(moduleId, '.group_nb');
        var $groupNb = $groupNbWrapper.find('span');

        $groupNb
        .text(totalNb)
        .attr('title', M.util.get_string('totalreactions', 'block_point_view', totalNb));

        $groupNbWrapper
        .toggleClass('invisible', totalNb === 0)
        .toggleClass('voted', reactionVotedArray[moduleId] !== Reactions.none);

        // Adjust the size to fit within a fixed space (useful for the green dot).
        var digits = Math.min(('' + totalNb).length, 5);
        $groupNb.css({
            'right': Math.max(0.25 * (digits - 2), 0) + 'em',
            'transform': 'scaleX(' + (1.0 + 0.03 * digits * digits - 0.35 * digits + 0.34) + ')'
        });
    }

    /**
     * Update a reaction number of votes.
     * @param {Number} moduleId Module ID.
     * @param {String} reactionName The reaction to update the number of.
     * @param {Number} diff Difference to apply (e.g. +1 for adding a vote, -1 for removing a vote).
     * @param {Boolean} isSelected Whether the reaction we are updating is the one now selected by user.
     */
    function updateReactionNb(moduleId, reactionName, diff, isSelected) {
        var $reactionNb = $get(moduleId, '.reactionnb[data-reactionname="' + reactionName + '"]');
        var nbReaction = parseInt($reactionNb.text()) + diff;
        $reactionNb
        .text(nbReaction)
        .toggleClass('nbselected', isSelected);

        $get(moduleId, '.reaction img[data-reactionname="' + reactionName + '"]')
        .toggleClass('novote', nbReaction === 0);
    }

    /**
     * Set up animations to swap between reactions preview and vote interface.
     * @param {Number} moduleId Module ID.
     */
    function setupReactionsAnimation(moduleId) {

        // Helpers to resize images for animations.
        var reactionImageSizeForRatio = function(ratio) {
            return {
                top: 15 - (10 * ratio),
                left: 10 - (10 * ratio),
                height: 20 * ratio
            };
        };
        var groupImageSizeForRatio = function(ratio) {
            return {
                left: -10 + (10 * ratio),
                height: 20 * ratio
            };
        };

        var $moduleInteractionsToHide = $(
            '#module-' + moduleId + ' button[data-action="toggle-manual-completion"],' +
            '#module-' + moduleId + ' .activity-info .automatic-completion-conditions > span.badge:first-of-type,' +
            '#module-' + moduleId + ' .activity-information [data-region="completionrequirements"],' +
            '#module-' + moduleId + ' .activity-grid > .activity-completion,' +
            '#module-' + moduleId + ' .activity-groupmode-info');

        // Animation sequence to hide reactions preview and show vote interface.
        var showReactions = function() {
            $get(moduleId, '.reactions').removeClass('invisible');

            $get(moduleId, '.group_img')
            .css({'pointer-events': 'none'})
            .animate(groupImageSizeForRatio(0), 300)
            .hide(0);

            $get(moduleId, '.group_nb').delay(200).hide(300);

            $moduleInteractionsToHide
            .delay(200).queue(function(next) {
                // Use opacity transition for a smooth hiding.
                $(this).css({
                    opacity: 0,
                    transition: 'opacity 0.3s ease-in-out'
                });
                next();
            }).delay(300).queue(function(next) {
                // Actually make the element invisible to avoid accidental clicking on transparent element.
                $(this).addClass('invisible');
                next();
            });

            ['easy', 'better', 'hard'].forEach(function(reaction, index) {
                var delay = 50 + 150 * index; // Easy: 50, better: 200, hard: 350.

                $get(moduleId, '.reaction img[data-reactionname="' + reaction + '"]')
                .delay(delay).animate(reactionImageSizeForRatio(1), 300)
                .css({'pointer-events': 'auto'});

                $get(moduleId, '.reactionnb[data-reactionname="' + reaction + '"]')
                .delay(delay + 300)
                .queue(function(next) {
                    $(this).removeClass('invisible');
                    next();
                });
            });
        };

        // Animation sequence to hide vote interface and show reaction preview.
        var hideReactions = function() {
            ['hard', 'better', 'easy'].forEach(function(reaction, index) {
                var delay = 50 + 250 * index; // Hard: 50, better: 300, easy: 550.
                $get(moduleId, '.reaction img[data-reactionname="' + reaction + '"]')
                .css({'pointer-events': 'none'})
                .delay(delay).animate(reactionImageSizeForRatio(0), 500);

                $get(moduleId, '.reactionnb[data-reactionname="' + reaction + '"]')
                .delay(delay)
                .queue(function(next) {
                    $(this).addClass('invisible');
                    next();
                });
            });

            // Show the reaction group image with nice animation.
            $get(moduleId, '.group_img')
            .delay(500)
            .show(0)
            .animate(groupImageSizeForRatio(1), 300)
            .queue(function(next) {
                $get(moduleId, '.reactions').addClass('invisible');
                next();
            })
            .css({'pointer-events': 'auto'});

            $get(moduleId, '.group_nb').delay(600).show(0);

            $moduleInteractionsToHide
            .delay(600).queue(function(next) {
                $(this).removeClass('invisible');
                // Use opacity transition for a smooth showing back.
                $(this).css({
                    opacity: 1,
                    transition: 'opacity 0.3s ease-in-out'
                });
                next();
            });
        };

        // Setup some timeouts and locks to trigger animations.
        var reactionsVisible = false;
        var groupTimeout = null;
        var reactionsTimeout = null;

        var triggerHideReactions = function() {
            reactionsTimeout = null;
            reactionsVisible = false;
            hideReactions();
        };

        var triggerShowReactions = function() {
            groupTimeout = null;
            reactionsVisible = true;
            showReactions();
            clearTimeout(reactionsTimeout);
            reactionsTimeout = setTimeout(triggerHideReactions, 2000); // Hide reactions after 2 seconds if mouse is already out.
        };

        // Reactions preview interactions.
        $get(moduleId, '.group_img')
        .mouseover(function() {
            $(this).stop().animate(groupImageSizeForRatio(1.15), 100); // Widen image a little on hover.
            groupTimeout = setTimeout(triggerShowReactions, 300); // Show vote interface after 0.3s hover.
        })
        .mouseout(function() {
            if (!reactionsVisible) {
                // Cancel mouseover actions.
                clearTimeout(groupTimeout);
                $(this).stop().animate(groupImageSizeForRatio(1), 100);
            }
        })
        .click(triggerShowReactions); // Show vote interface instantly on click.

        // Reactions images interactions.
        $get(moduleId, '.reaction img')
        .mouseover(function() {
            $(this).stop().animate(reactionImageSizeForRatio(2), 100); // Widen image a little on hover.
        })
        .mouseout(function() {
            $(this).stop().animate(reactionImageSizeForRatio(1), 100);
        });

        // Vote interface zone interactions
        $get(moduleId, '.reactions')
        .mouseout(function() {
            clearTimeout(reactionsTimeout);
            reactionsTimeout = setTimeout(triggerHideReactions, 1000); // Hide vote interface after 1s out of it.
        })
        .mouseover(function() {
            clearTimeout(reactionsTimeout);
        });
    }

    return {
        init: function(courseId) {

            // Wait that the DOM is fully loaded.
            $(function() {

                var data = $('.block_point_view[data-blockdata]').data('blockdata');

                var cmid = null; // If this page is a course module view, retrieve the module ID.
                document.body.classList.forEach(function(bodyClass) {
                    var matches = bodyClass.match(/cmid-(\d+)/);
                    cmid = matches ? matches[1] : cmid;
                });

                callOnModulesListLoad(function(root) {
                    if (typeof root === 'undefined') {
                        root = document.body;
                    }
                    setUpDifficultyTracks(root, data.difficultylevels, data.trackcolors, cmid);
                    setUpReactions(root, courseId, data.moduleswithreactions, data.reactionstemplate, data.pix, cmid);
                });

            });
        }
    };
});