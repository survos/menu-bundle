# survos/menu-bundle

Menus built by event listeners and drawn by composable Twig themes, on top of [knplabs/knp-menu](https://github.com/KnpLabs/KnpMenu).

KnpMenu's item tree, factory and matcher stay; what this bundle replaces is the rendering. Instead of one
template that `extends '@KnpMenu/menu.html.twig'` and redefines everything, a menu is drawn by a **stack of
themes** composed at runtime with Twig's `BlockChain` (Twig 3.29+), the way form themes work. A theme holds
only the blocks it changes.

The bundle reaches knp only through `Knp\Menu\FactoryInterface` and `Knp\Menu\Matcher\MatcherInterface`.
KnpMenuBundle provides those today; nothing here uses its service ids or classes, so it can be replaced later.

## Fill a menu

A menu is an event name. `MenuSlot` holds the page regions a layout draws; any other string is a custom menu.

```php
use Survos\MenuBundle\Attribute\AsMenuListener;
use Survos\MenuBundle\Event\MenuEvent;
use Survos\MenuBundle\MenuSlot;

final class ProjectMenu
{
    #[AsMenuListener(MenuSlot::PAGE_ACTIONS)]
    public function actions(MenuEvent $event): void
    {
        $project = $event->getOption('project');            // the page's entity, see below
        $event->menu->addChild('edit', ['route' => 'project_edit', 'routeParameters' => ['id' => $project->id]]);
    }
}
```

`#[AsMenuListener]` is `#[AsEventListener]` with the menu name as its first argument.

## Draw a menu

```twig
{{ menu(PAGE_ACTIONS) }}                                  {# slot names are Twig globals #}
{{ menu('card.actions', {card: card}) }}                  {# options reach the listeners #}
{{ menu(SIDEBAR, {}, {theme: '@App/menu/sidebar.html.twig'}) }}
{% if menu_has_items(SIDEBAR) %}...{% endif %}

<twig:menu name="NAVBAR_END" />
<twig:menu name="card.actions" :options="{card: card}" theme="@App/menu/card.html.twig" />
```

`menu()` also hands the listeners the calling template's object variables: a page rendering `project` needs
no menu code. A component cannot see its caller's variables, so `<twig:menu>` gets only what is in `:options`.

## Themes

The bottom of every stack is `@SurvosMenu/theme/base.html.twig`: plain `<ul>/<li>/<a>`, no CSS framework.
Each item is drawn by the block named after its kind: `item_link`, `item_submenu`, `item_heading`,
`item_divider`, `item_search`; those call `link`, `icon`, `label`, `badge`, `submenu`, `submenu_item`.

```twig
{# templates/menu/shout.html.twig: the whole theme #}
{% block label %}{{ item.label|upper }}{% endblock %}
```

`block('label')` inside the base theme's submenu resolves through the stack, so that one block changes every
label, at every depth. Nothing extends anything. Precedence, highest first: the call's `theme`/`themes`, the
menu's spec (from a `MenuSpecProviderInterface`, e.g. tabler's slot themes), then the base.

Templates only print. `item` is a `MenuItemView`: kind (`ItemKind`), translated label, `uri`, `icon`,
`badge` (`Badge`), `current`/`ancestor` already matched, `attributes`/`linkAttributes`, `children`. Hidden
items, dropdowns whose children were all filtered out, and stray dividers are removed before a theme sees the
tree. `menu_attrs(attributes, defaults)` prints attributes, merging `class` and handling `true`/`false`.

KnpMenu extras understood (in `MenuViewFactory`, nowhere else): `icon`, `badge`, `submenu`, `heading`,
`divider`, `form` (search box), `translation_domain`, `safe_label`, `data_attributes`, `tooltip`, and the
`divider_prepend`/`divider_append` attributes. Anything else reaches the theme as `item.extra('name')`.

The renderer is also registered with knp as `survos`: `knp_menu_render(menu, {themes: [...]}, 'survos')`.

## Integrating a UI bundle

- `MenuSpecProviderInterface`: how each named menu is drawn by default (themes, `rootAttributes`, classes).
- `MenuOptionsResolverInterface`: what listeners see as options (configured defaults, controller arguments).
- `MenuDispatcher::createEvent()`: dispatch a `MenuEvent` subclass.

survos/tabler-bundle does all three; see its `docs/menus.md`.

### Link decoration at rendering time

Optional integrations can implement `Renderer\LinkAttributesDecoratorInterface` and tag their service `survos_menu.link_attributes_decorator`. It receives the item and translated label after composable filtering and returns additional attributes on the existing target. Legacy templates use `menu_link_attributes(item)` at the point they render a link (then escape attributes). This generic hook powers optional tour annotations without rebuilding menus or depending on a JavaScript renderer. Custom decorators must not treat browser visibility as authorization.
