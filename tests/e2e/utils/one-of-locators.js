const waitForLocator = ( locator ) => {
	return locator.waitFor().then( () => locator );
};

export default function oneOfLocators( ...locators ) {
	return Promise.race( locators.map( waitForLocator ) );
}
