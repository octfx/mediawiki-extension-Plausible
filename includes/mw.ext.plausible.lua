local Plausible = {}
local libraryUtil = require( 'libraryUtil' )
local checkType = libraryUtil.checkType
local checkTypeMulti = libraryUtil.checkTypeMulti
local php


function Plausible.topPages( days )
    if days then
        checkType( 'Plausible.topPages', 1, days, 'number' )

        return php.getTopPagesDays( days )
    end

    return php.getTopPages()
end


function Plausible.pageData( titles, days )
    days = days or 30

    checkTypeMulti( 'Plausible.pageData', 1, titles, { 'string', 'table' } )
    checkType( 'Plausible.pageData', 2, days, 'number' )

    if type( titles ) == 'string' then
        titles = { titles }
    end

    return php.getPageData( titles, days )
end


function Plausible.siteData( days )
    days = days or 30
    checkType( 'Plausible.siteData', 1, days, 'number' )

    return php.getSiteData( days )
end


function Plausible.setupInterface()
    -- Boilerplate
    Plausible.setupInterface = nil
    php = mw_interface
    mw_interface = nil

    -- Register this library in the "mw" global
    mw = mw or {}
    mw.ext = mw.ext or {}
    mw.ext.plausible = Plausible

    package.loaded['mw.ext.plausible'] = Plausible
end


return Plausible