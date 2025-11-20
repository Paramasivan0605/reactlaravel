import React, { useEffect, useState } from 'react';
import axios from 'axios';
import LocationHeader from '../components/LocationHeader';
import DeliveryModal from '../components/DeliveryModal';
import CategoryList from '../components/CategoryList';
import FoodGrid from '../components/FoodGrid';
import Loader from '../components/Loader';
import '../css/location-menu.css';

export default function LocationMenu({ locationId }) {
  const [loading, setLoading] = useState(true);
  const [location, setLocation] = useState(null);
  const [categories, setCategories] = useState([]);
  const [foodMenu, setFoodMenu] = useState([]);
  const [currency, setCurrency] = useState('RM');
  const [activeCategory, setActiveCategory] = useState('all');
  const [deliveryModalOpen, setDeliveryModalOpen] = useState(false);

  useEffect(() => {
    loadData();
  }, []);

  async function loadData() {
    try {
      axios.defaults.withCredentials = true;
      const res = await axios.get(`/api/location/${locationId}/menu`);
      setLocation(res.data.location);
      setCategories(res.data.categories || []);
      setFoodMenu(res.data.foodMenu || []);
      setCurrency(res.data.currency || 'RM');
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  }

  if (loading) return <Loader />;

  return (
    <div className="lm-root">
      <DeliveryModal
        open={deliveryModalOpen}
        onClose={() => setDeliveryModalOpen(false)}
        locationId={locationId}
      />

      <LocationHeader
        location={location}
        currency={currency}
        onChooseDelivery={() => setDeliveryModalOpen(true)}
      />

      <div className="lm-categories-wrapper">
        <CategoryList
          categories={categories}
          activeCategory={activeCategory}
          setActiveCategory={setActiveCategory}
        />
      </div>

      <main className="lm-main container">
        <FoodGrid
          foods={foodMenu}
          activeCategory={activeCategory}
          currency={currency}
        />
      </main>
    </div>
  );
}
