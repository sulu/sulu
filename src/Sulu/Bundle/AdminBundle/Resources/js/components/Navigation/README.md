The `Navigation` is used to display the navigation in our application.

Example with all props:

```javascript
const handleLogoutClick = () => {
    // Do what every you like..
    alert('Handle logout click');
};

const handleProfileClick = () => {
    // Do what ever you like..
    alert('Handle profile click');
};

const handleNavigationClick = (value) => {
    // Do what ever you like..
    alert('Navigate to ' + value);
};

<div style={{height: '800px'}}>
    <Navigation
        title="sulu.io"
        username="John Travolta"
        userImage={'http://lorempixel.com/200/200'}
        onItemClick={handleNavigationClick}
        onLogoutClick={handleLogoutClick}
        onProfileClick={handleProfileClick}
        suluVersion="2.0.0-RC1"
        suluVersionLink="http://link.com"
        appVersion="1.0.0"
        appVersionLink="http://link.com"
    >
        <Navigation.Item value="search" title="Search" icon="su-search" />
        <Navigation.Item value="webspaces" title="Webspaces" icon="su-webspace" />
        <Navigation.Item value="media" title="Media" icon="fa-image" />
        <Navigation.Item value="articles" title="Article" icon="fa-newspaper-o" />
        <Navigation.Item value="snippets" title="Snippets" icon="fa-sticky-note-o" />
        <Navigation.Item value="contact" title="Contact" icon="fa-user">
            <Navigation.Item value="contact_1" title="Contact 1" />
            <Navigation.Item value="contact_2" title="Contact 2" active={true} />
            <Navigation.Item value="contact_3" title="Contact 3" />
        </Navigation.Item>
        <Navigation.Item value="settings" title="Settings" icon="fa-gear">
            <Navigation.Item value="setting_1" title="Setting 1" />
            <Navigation.Item value="setting_2" title="Setting 2" />
            <Navigation.Item value="setting_3" title="Setting 3" />
        </Navigation.Item>
    </Navigation>
</div>
```

Example with the minimal required props:

```javascript
const handleLogoutClick = () => {
    // Do what every you like..
    alert('Handle logout click');
};

const handleProfileClick = () => {
    // Do what ever you like..
    alert('Handle profile click');
};

const handleNavigationClick = (value) => {
    // Do what ever you like..
    alert('Navigate to ' + value);
};

<div style={{height: '500px'}}>
    <Navigation
        onItemClick={handleNavigationClick}
        onLogoutClick={handleLogoutClick}
        onProfileClick={handleProfileClick}
        suluVersion="2.0.0-RC1"
        suluVersionLink="http://link.com"
        title="sulu.io"
        username="John Terence Maximilian Travolta"
    >
        <Navigation.Item value="search" title="Search" icon="su-search" />
        <Navigation.Item value="webspaces" title="Webspaces" icon="su-webspace" />
    </Navigation>
</div>
```
