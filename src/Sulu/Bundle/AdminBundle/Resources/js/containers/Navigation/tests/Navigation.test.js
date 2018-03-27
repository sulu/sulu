/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render} from 'enzyme';
import Navigation from '../Navigation';

jest.mock('../registries/NavigationRegistry', () => ({
    get: jest.fn().mockReturnValue([
        {
            id: '111-111',
            title: 'Test Navigation',
            icon: 'su-options',
            mainRoute: 'sulu_admin.form_tab',
        },
        {
            id: '222-222',
            title: 'Test Navigation 2',
            icon: 'su-article',
            mainRoute: 'sulu_article.datagrid',
            childRoutes: ['sulu_article.form', 'sulu_article.form'],
        },
        {
            id: '333-333',
            title: 'Test Navigation with Children',
            icon: 'su-options',
            items: [
                {
                    id: '333-child1',
                    title: 'Test Navigation Child 1',
                    icon: 'su-options',
                    mainRoute: 'sulu_admin.form_tab',
                },
                {
                    id: '333-child2',
                    title: 'Test Navigation Child 2',
                    icon: 'su-article',
                    mainRoute: 'sulu_article.datagrid',
                    childRoutes: ['sulu_article.form', 'sulu_article.form'],
                },
            ],
        },
    ]),
}));

test('Should render navigation', () => {
    const router = {
        route: {
            name: 'sulu_admin.form_tab',
            view: 'form_tab',
        },
    };
    const handleNavigate = jest.fn();

    const form = render(<Navigation router={router} onNavigate={handleNavigate} />);
    expect(form).toMatchSnapshot();
});
