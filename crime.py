# Step 1: Import libraries
import pandas as pd
from prophet import Prophet
import joblib

# Step 2: Load the dataset
df = pd.read_csv('sf_crime.csv')  # Replace with your dataset filename
print(df.head())

# Step 3: Preprocess the data
df = df[['Dates', 'Category']]  # Replace with your actual columns
df = df.rename(columns={'Dates': 'ds', 'Category': 'y'})
df['ds'] = pd.to_datetime(df['ds'])
df = df.groupby('ds').size().reset_index(name='y')
print(df.head())

# Step 4: Train the Prophet model
model = Prophet()
model.fit(df)

# Step 5: Make predictions
future = model.make_future_dataframe(periods=365)
forecast = model.predict(future)
print(forecast[['ds', 'yhat', 'yhat_lower', 'yhat_upper']].tail())

# Step 6: Save the model
joblib.dump(model, 'crime_prediction_model.pkl')
print("Model saved as 'crime_prediction_model.pkl'")