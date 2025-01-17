const symplecticPublications = (function () {
	
	'use strict';
	
	
	return {
		
		init: function (settings)
		{
			// Locate the anchor point
			const anchorPoint = 'h2#publications';
			const publicationsHeading = document.querySelector (anchorPoint);
			
			// End if no anchor to attach to
			if (!publicationsHeading) {return;}
							
			// Add styles
			const styles = `<style type="text/css">
				#symplecticswitch {margin-bottom: 20px;}
				#symplecticswitch p {float: right; border: 1px solid #603; background-color: #f7f7f7; padding: 5px;}
			</style>`;
			publicationsHeading.insertAdjacentHTML ('beforeend', styles);
			
			// Add checkbox container
			publicationsHeading.insertAdjacentHTML ('beforebegin', '<div id="symplecticswitch" />');
			
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
			
			// Define a token for no publications found
			const noPublicationsFound = 'NO_SUCH_USER';
			
			// Attempt to get the HTML (will be run asyncronously) from the API for this user, or return 404
			const apiUrl = settings.baseUrl + '/people/' + settings.username + '/html';
			fetch (apiUrl)
			.then (function (response) {
				if (response.status == 204) {return noPublicationsFound;}		// 204 indicates no such user; ideally this string would appear in the HTTP response, but 204 deliberately sends 0 length content
				if (response.ok) {return response.text ();}
				else {throw new Error ('Error: ' + response.status);}
			})
			.then (function (symplecticpublicationsHtml) {
				
				// Do nothing if no publications found
				if (symplecticpublicationsHtml == noPublicationsFound) {return;}
				
				// Surround existing (manual) publications block with a div, automatically, unless already present
				let manualPublicationsDiv = document.querySelector ('#manualpublications');
				if (!manualPublicationsDiv) {
					const publicationSectionElements = nextUntil (publicationsHeading, 'h2');
					manualPublicationsDiv = wrapAll (publicationSectionElements, 'manualpublications');
					publicationsHeading.after (manualPublicationsDiv);
				}
				
				// Add a location for the new publications block
				manualPublicationsDiv.insertAdjacentHTML ('afterend', '<div id="symplecticpublications" />');
				const symplecticPublicationsDiv = document.querySelector ('#symplecticpublications');
				
				// Set initial styles
				manualPublicationsDiv.style.display = 'none';
				symplecticPublicationsDiv.style.display = 'block';
				
				// Add the HTML from the API
				symplecticPublicationsDiv.innerHTML = symplecticpublicationsHtml;
				
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
					const symplecticSwitch = document.querySelector ('#symplecticswitch');
					symplecticSwitch.innerHTML = '<p><label for="symplectic">Show Symplectic version? </label><input type="checkbox" id="symplectic" name="symplectic" /></p>';
					
					// Check by default when live
					symplecticSwitch.querySelector ('input[type="checkbox"]').checked = true;
					
					// Add helpful links
					let helpfulLinks = '';
					helpfulLinks += '<ul class="nobullet right spaced">';
					helpfulLinks += '<li class="primaryaction"><a href="' + settings.website + '" title="Edit this list, by making changes in the University\'s publications database, Symplectic"><img src="/images/icons/pencil.png" /> Edit my publications</a></li>';
					helpfulLinks += '<li class="primaryaction"><a href="' + settings.baseUrl + '/bookcover.html" title="Add a book cover"><img src="/images/icons/book_open.png" /> Add book cover(s)</a></li>';
					helpfulLinks += '<li class="primaryaction"><a href="' + settings.baseUrl + '/quickstart.pdf?"><img src="/images/icons/page.png" /> Help guide (PDF)</a></li>';
					helpfulLinks += '</ul>';
					symplecticPublicationsDiv.insertAdjacentHTML ('beforebegin', helpfulLinks);
					
					// Toggle div blocks when checkbox is on
					document.querySelector ('#symplectic').addEventListener ('click', function (e) {
						symplecticPublicationsDiv.style.display = (e.target.checked ? 'block' : 'none');
						manualPublicationsDiv.style.display = (e.target.checked ? 'none' : 'block');
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