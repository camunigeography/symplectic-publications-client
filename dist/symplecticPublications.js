const symplecticPublications = (function () {
	
	'use strict';
	
	
	return {
		
		init: function (settings)
		{
			// Add styles
			const styles = `<style type="text/css">
				#symplecticswitch {margin-bottom: 20px;}
				#symplecticswitch p {float: right; border: 1px solid #603; background-color: #f7f7f7; padding: 5px;}
				#symplecticpublications.proposed {border-top: 1px dashed #ccc; border-bottom: 1px dashed #ccc; padding: 5px 0; background-color: #f7f7f7;}
				#symplecticpublications img.bookcover {min-width: 170px; margin: 5px 10px 12px 0; box-shadow: 5px 5px 10px 0 #aaa;}
			</style>`;
			document.querySelector ('#publications').insertAdjacentHTML ('beforeend', styles);
			
			// Add checkbox container
			document.querySelector ('#publications').insertAdjacentHTML ('beforebegin', '<div id="symplecticswitch" />');
			
			// Function to get the elements until a selector; see: https://gomakethings.com/how-to-get-all-sibling-elements-until-a-match-is-found-with-vanilla-javascript/
			const nextUntil = function (element, selector)
			{
				const siblings = [];
				element = element.nextElementSibling;
				while (element) {
					if (element.matches (selector)) break;
					siblings.push (element);
					element = element.nextElementSibling;
				}
				return siblings;
			};
			
			// Function to wrapper elements in a div; see: https://stackoverflow.com/a/48389433
			const wrapAll = function (elements, wrapperId)
			{
				const wrapper = document.createElement ('div');
				wrapper.setAttribute ('id', wrapperId);
				elements.forEach (function (child) {
					wrapper.appendChild (child);
				});
				return wrapper;
			};
			
			// Attempt to get the HTML (will be run asyncronously) from the API for this user, or return 404
			const apiUrl = settings.baseUrl + '/people/' + settings.username + '/html';
			fetch (apiUrl)
			.then (function (response) {
				if (response.ok) {return response.text ();}
				else {throw new Error ('Error: ' + response.status);}
			})
			.then (function (symplecticpublicationsHtml) {
				
				// Surround existing (manual) publications block with a div, automatically, unless already present
				if (!document.querySelector ('#manualpublications')) {
					const publicationSectionElements = nextUntil (document.querySelector ('h2#publications'), 'h2');
					const manualPublicationsDiv = wrapAll (publicationSectionElements, 'manualpublications');
					document.querySelector ('h2#publications').after (manualPublicationsDiv);
				}
				
				// Add a location for the new publications block
				document.querySelector ('#manualpublications').insertAdjacentHTML ('afterend', '<div id="symplecticpublications" />');
				if (settings.previewMode) {
					document.querySelector ('#symplecticpublications').classList.add ('proposed');
				}
				
				// Determine whether to show or hide by default
				if (settings.previewMode) {
					document.querySelector ('#symplecticpublications').style.display = 'none';
				} else {
					document.querySelector ('#manualpublications').style.display = 'none';
					document.querySelector ('#symplecticpublications').style.display = 'block';
				}
				
				// Add the HTML from the API
				document.querySelector ('#symplecticpublications').innerHTML = symplecticpublicationsHtml;
				
				// Execute scripts, as innerHTML will not execute <script> tags; see: https://stackoverflow.com/questions/1197575/
				var parser = new DOMParser ();
				var documentHtml = parser.parseFromString (symplecticpublicationsHtml, 'text/html');
				var scriptTags = documentHtml.getElementsByTagName ('script');
				Array.from (scriptTags).forEach (function (scriptTag) {
					eval (scriptTag.innerHTML);		// Known source
				});
				
				// Show tools if required
				if (settings.showTools) {
					
					// Add checkbox
					document.querySelector ('#symplecticswitch').innerHTML = '<p><label for="symplectic">Show Symplectic version? </label><input type="checkbox" id="symplectic" name="symplectic" /></p>';
					
					// Check by default when live
					if (!settings.previewMode) {
						document.querySelector ('#symplecticswitch input[type="checkbox"]').checked = true;
					}
					
					// Add helpful links
					let helpfulLinks = '';
					helpfulLinks += '<ul class="nobullet right spaced">';
					helpfulLinks += (settings.previewMode ? '<li>This listing goes live ' + settings.goLiveDate + '.</li>' : '');
					helpfulLinks += '<li class="primaryaction"><a href="' + settings.website + '" title="Edit this list, by making changes in the University\'s publications database, Symplectic"><img src="/images/icons/pencil.png" /> Edit my publications</a></li>';
					helpfulLinks += '<li class="primaryaction"><a href="' + settings.baseUrl + '/bookcover.html" title="Add a book cover"><img src="/images/icons/book_open.png" /> Add book cover(s)</a></li>';
					helpfulLinks += '<li class="primaryaction"><a href="' + settings.baseUrl + '/quickstart.pdf?"><img src="/images/icons/page.png" /> Help guide (PDF)</a></li>';
					helpfulLinks += '</ul>';
					document.querySelector ('#symplecticpublications').insertAdjacentHTML ('beforebegin', helpfulLinks);
					
					// Toggle div blocks when checkbox is on
					document.querySelector ('#symplectic').addEventListener ('click', function (e) {
						document.querySelector ('#symplecticpublications').style.display = (e.target.checked ? 'block' : 'none');
						document.querySelector ('#manualpublications').style.display = (e.target.checked ? 'none' : 'block');
					});
				}
			})
			.catch (function (error) {
				//console.log (error);
				document.querySelector ('#symplecticswitch').innerHTML = '<p>(No publications found in Symplectic.)</p>';
			});
		}
	}
}) ();