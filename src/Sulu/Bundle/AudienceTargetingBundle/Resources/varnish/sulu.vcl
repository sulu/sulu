/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

import header;

sub sulu_audience_targeting_recv {
    if (req.http.Cookie ~ "_svtg" && req.http.Cookie ~ "_svs") {
        set req.http.X-Sulu-Target-Group = regsub(req.http.Cookie, ".*_svtg=([^;]+).*", "\1");
    } elseif (req.restarts == 0) {
        set req.http.X-Forwarded-Url = req.url;
        set req.http.X-Sulu-Target-Group = regsub(req.http.Cookie, ".*_svtg=([^;]+).*", "\1");
        set req.url = "/_sulu_target_group";
    } elseif (req.restarts > 0) {
        set req.url = req.http.X-Forwarded-Url;
        unset req.http.X-Forwarded-Url;
    }
}

sub sulu_audience_targeting_deliver {
    if (resp.http.X-Sulu-Target-Group) {
        set req.http.X-Sulu-Target-Group = resp.http.X-Sulu-Target-Group;
        set req.http.Set-Cookie = "_svtg=" + resp.http.X-Sulu-Target-Group + "; expires=Tue, 19 Jan 2038 03:14:07 GMT; path=/;";

        return (restart);
    }

    if (resp.http.Vary ~ "X-Sulu-Target-Group") {
        set resp.http.Cache-Control = regsub(resp.http.Cache-Control, "max-age=(\d+)", "max-age=0");
        set resp.http.Cache-Control = regsub(resp.http.Cache-Control, "s-maxage=(\d+)", "s-maxage=0");
    }

    if (req.http.Set-Cookie) {
        set resp.http.Set-Cookie = req.http.Set-Cookie;
        header.append(resp.http.Set-Cookie, "_svs=" + now + "; path=/;");
    }
}
