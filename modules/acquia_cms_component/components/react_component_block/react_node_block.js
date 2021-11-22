(function (drupalApi) {
  let drupalApiOj = new drupalApi();
  const Node = createReactClass({
    getInitialState: function() {
      this.state = {
        data: [],
        loaded: false,
        isToggleOn: true
      };
      return {};
    },
    handleClick(e) {
      e.preventDefault();
      this.setState(prevState => ({
        isToggleOn: !prevState.isToggleOn
      }));
      console.log("Clicked");
      return false;
    },
    componentDidMount() {
      const attributes = this.props.attributes;
      let _this = this, params = { sort: "-created" };
      drupalApiOj.setEndpoint("node/" + attributes['data-type']);
      if (attributes.hasOwnProperty('data-display-item')) {
        params['page'] = {
          "limit": attributes['data-display-item'],
          "offset": "5"
        };
      }
      drupalApiOj.setParams(params);
      drupalApiOj.callApi((data) => _this.setState({ data: data.data, loaded: true }));
    },
    render() {
      const { data, loaded } = this.state;
      if (!loaded) {
        return <div className="loader" />;
      }
      if (data.length === 0) {
        return <i>No results</i>;
      }
      return (
        <div>
          <ul>
            {data.map(article =>
              <li>
                <a href={article.attributes.path.alias} onClick={this.handleClick}>{article.attributes.title}</a>
              </li>
            )}
          </ul>
        </div>
      );
    }
  });
  let elements = document.getElementsByClassName('react_component_block');
  for (let i = 0; i < elements.length; ++i) {
    let element = elements[i];
    let attributes = {};
    for (let j = 0, atts = element.attributes, n = atts.length; j < n; j++) {
      if (atts[j].nodeName !== "class" && atts[j].nodeName !== "id" ) {
        attributes[atts[j].nodeName]= atts[j].nodeValue;
      }
    }
    ReactDOM.render(<Node attributes={attributes} />, element);
  }
})(DrupalApi);
