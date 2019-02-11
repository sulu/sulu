// @flow
import Enzyme from 'enzyme';
import Adapter from 'enzyme-adapter-react-16';

Enzyme.configure({adapter: new Adapter()});

jest.mock('sulu-admin-bundle/services/Config', () => ({
    endpoints: {
        'config': 'config_url',
        'translations': 'translations_url',
        'loginCheck': 'login_check_url',
        'logout': 'logout_url',
        'profileSettings': 'profile_settings_url',
        'reset': 'reset_url',
        'resetResend': 'reset_resend_url',
        'resources': 'resources_url/:resource',
    },
    translations: ['en', 'de'],
    fallbackLocale: 'en',
}));
