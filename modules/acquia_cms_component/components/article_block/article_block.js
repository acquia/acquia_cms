const Articles = createReactClass({
  getInitialState: function() {
    this.state = {
      data: [],
      loaded: false,
    };
    return({message: null});
  },
  componentDidMount() {
    // Map select option with component configuration form select.
    const articleBlock = document.getElementById('article-block-component').parentElement;
    const selectOption = ['5', '10', '20', '30', '50', 'All'];
    const dataDisplayItem = articleBlock.getAttribute('data-display-item');
    const itemToDisplay = selectOption[dataDisplayItem];
    let endpoint = '/jsonapi/node/article?sort=-created';
    if (itemToDisplay && itemToDisplay !=='All') {
      endpoint = '/jsonapi/node/article?page[offset]=0&page[limit]=' + itemToDisplay;
    }
    fetch(endpoint)
      .then(response => response.json())
      .then(data => this.setState({ data: data.data, loaded: true }));
  },
  render() {
    const { data, loaded } = this.state;
    if (!loaded) {
      return <div className="loader" />;
    }
    if (data.length === 0) {
      return <p>No results</p>;
    }
    return (
      <div>
        <ul>
          {data.map(article =>
            <li>
              <a href={article.attributes.path.alias}>{article.attributes.title}</a>
            </li>
          )}
        </ul>
      </div>
    );
  }
});

ReactDOM.render(<Articles />,
  document.getElementById('article-block-component')
);
